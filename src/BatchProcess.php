<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch;

use Ang3\Doctrine\ORM\Batch\Exception\ProcessException;
use Ang3\Doctrine\ORM\Batch\Exception\ProcessExceptionInterface;
use Ang3\Doctrine\ORM\Batch\Exception\RollbackFailureException;
use Ang3\Doctrine\ORM\Batch\Handler\BatchHandlerInterface;
use Ang3\Doctrine\ORM\Batch\Iterator\BatchIterator;
use Ang3\Doctrine\ORM\Batch\Iterator\BatchIteratorInterface;
use Ang3\Doctrine\ORM\Batch\Iterator\CallableIterator;
use Ang3\Doctrine\ORM\Batch\Iterator\EntityIdentifiersIterator;
use Ang3\Doctrine\ORM\Batch\Iterator\OrmQueryIterator;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * A batch process for an entity manager.
 *
 * This class manages a batch process over an EntityManager. It allows iterating over data,
 * processing entities in batches, and applying various options such as buffer size, transactional
 * entities, and custom callbacks for different stages of the process.
 */
class BatchProcess
{
    use OptionsTraits;

    public const DEFAULT_BUFFER_SIZE = 20;

    /**
     * Option keys.
     *
     * @internal
     */
    private const OPTION_BUFFER_SIZE = 'buffer_size';
    private const OPTION_DISABLED_ID_GENERATORS = 'disabled_id_generators';
    private const OPTION_TRANSACTIONAL_ENTITIES = 'transactional_entities';
    private const OPTION_ON_FIRST_ITERATION_CALLABLE = 'on_first_iteration';
    private const OPTION_ON_FLUSH_CALLABLE = 'on_flush_callable';
    private const OPTION_ROLLBACK_CALLABLE = 'rollback_callable';
    private const OPTION_DRY_RUN = 'dry_run';

    /**
     * Default options.
     *
     * @internal
     *
     * @var array<string, mixed>
     */
    private const DEFAULT_OPTIONS = [
        self::OPTION_BUFFER_SIZE => self::DEFAULT_BUFFER_SIZE,
        self::OPTION_DISABLED_ID_GENERATORS => [],
        self::OPTION_TRANSACTIONAL_ENTITIES => [],
        self::OPTION_ON_FIRST_ITERATION_CALLABLE => null,
        self::OPTION_ON_FLUSH_CALLABLE => null,
        self::OPTION_ROLLBACK_CALLABLE => null,
        self::OPTION_DRY_RUN => false,
    ];

    /**
     * Middlewares applied to the EntityManager.
     *
     * @var Middleware[]
     */
    private array $middlewares = [];

    /**
     * The total runtime of the batch process.
     */
    private float $runTime = 0.00;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface     $entityManager the Doctrine EntityManager
     * @param BatchIteratorInterface     $iterator      the batch iterator for processing data
     * @param BatchHandlerInterface|null $handler       the handler to process each iteration (optional)
     * @param int|null                   $bufferSize    the number of iterations before flushing (default is DEFAULT_BUFFER_SIZE)
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private BatchIteratorInterface $iterator,
        private ?BatchHandlerInterface $handler = null,
        ?int $bufferSize = null
    ) {
        $this->options = new OptionsBag(self::DEFAULT_OPTIONS);
        $this->setBufferSize($bufferSize ?: self::DEFAULT_BUFFER_SIZE);
    }

    /**
     * Creates a BatchProcess from data.
     *
     * @param EntityManagerInterface              $entityManager the Doctrine EntityManager
     * @param iterable<array-key, mixed>|callable $data          an iterable or a callable that returns an iterable of data
     */
    public static function iterateData(EntityManagerInterface $entityManager, iterable|callable $data): self
    {
        if (\is_callable($data)) {
            return self::create(
                $entityManager,
                new CallableIterator($data)
            );
        }
        /** @var iterable<array-key, mixed> $dataIterable */
        $dataIterable = $data;

        return self::create(
            $entityManager,
            new BatchIterator($dataIterable)
        );
    }

    /**
     * Creates a BatchProcess to iterate over a set of entities by their identifiers.
     *
     * @param EntityManagerInterface $entityManager the Doctrine EntityManager
     * @param class-string           $entityFqcn    the fully qualified class name of the entity
     * @param array<int|string>      $identifiers   an array of entity identifiers
     */
    public static function iterateEntities(EntityManagerInterface $entityManager, string $entityFqcn, array $identifiers): self
    {
        return self::create(
            $entityManager,
            new EntityIdentifiersIterator($entityFqcn, $identifiers)
        );
    }

    /**
     * Creates a BatchProcess from a Doctrine Query or QueryBuilder.
     *
     * @param Query|QueryBuilder $query a Doctrine Query or QueryBuilder object
     */
    public static function iterateQueryResult(Query|QueryBuilder $query): self
    {
        $query = $query instanceof QueryBuilder ? $query->getQuery() : $query;

        return self::create(
            $query->getEntityManager(),
            new OrmQueryIterator($query)
        );
    }

    /**
     * Creates a new BatchProcess instance.
     *
     * @param EntityManagerInterface $entityManager the Doctrine EntityManager
     * @param BatchIteratorInterface $iterator      the batch iterator
     */
    public static function create(EntityManagerInterface $entityManager, BatchIteratorInterface $iterator): self
    {
        return new self($entityManager, $iterator);
    }

    /**
     * Sets a new iterator for the batch process.
     *
     * @param BatchIteratorInterface $iterator the batch iterator
     */
    public function setIterator(BatchIteratorInterface $iterator): self
    {
        $this->iterator = $iterator;

        return $this;
    }

    /**
     * Sets the handler for the batch process.
     *
     * @param BatchHandlerInterface|null $handler the batch handler
     */
    public function setHandler(?BatchHandlerInterface $handler = null): self
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Sets the buffer size for the batch process.
     *
     * @param int $bufferSize the number of iterations before flushing
     */
    public function setBufferSize(int $bufferSize = self::DEFAULT_BUFFER_SIZE): self
    {
        $this->setOption(self::OPTION_BUFFER_SIZE, $bufferSize);

        return $this;
    }

    /**
     * Enables or disables "dry run" mode.
     *
     * In dry run mode, flush() are not performed during execution.
     */
    public function setDryRun(bool $dryRun): self
    {
        $this->setOption(self::OPTION_DRY_RUN, $dryRun);

        return $this;
    }

    /**
     * Disables the identifier generator for specified entity classes.
     *
     * @param string ...$classNames The fully qualified class names of entities.
     */
    public function disableIdGenerator(string ...$classNames): self
    {
        /** @var array<string> $data */
        $data = $this->getOption(self::OPTION_DISABLED_ID_GENERATORS);
        foreach ($classNames as $className) {
            if (!\in_array($className, $data, true)) {
                $data[] = $className;
            }
        }
        $this->setOption(self::OPTION_DISABLED_ID_GENERATORS, $data);

        return $this;
    }

    /**
     * Restores identifier generators for specified entity classes.
     *
     * @param array<string> $classNames an array of fully qualified class names
     */
    public function restoreIdGenerator(array $classNames): self
    {
        /** @var array<string> $data */
        $data = $this->getOption(self::OPTION_DISABLED_ID_GENERATORS);
        foreach ($classNames as $className) {
            $key = array_search($className, $data, true);
            if (false !== $key) {
                unset($data[$key]);
            }
        }
        $this->setOption(self::OPTION_DISABLED_ID_GENERATORS, $data);

        return $this;
    }

    /**
     * Restores all identifier generators.
     */
    public function restoreAllIdGenerators(): self
    {
        $this->setOption(self::OPTION_DISABLED_ID_GENERATORS, []);

        return $this;
    }

    /**
     * Adds an entity to the list of transactional entities.
     *
     * @param object $object the entity to add
     */
    public function addTransactionalEntity(object &$object): self
    {
        /** @var array<object> $data */
        $data = $this->getOption(self::OPTION_TRANSACTIONAL_ENTITIES);
        $data[] = &$object;
        $this->setOption(self::OPTION_TRANSACTIONAL_ENTITIES, $data);

        return $this;
    }

    /**
     * Removes an entity from the list of transactional entities.
     *
     * @param object $object the entity to remove
     */
    public function removeTransactionalEntity(object &$object): self
    {
        /** @var array<object> $data */
        $data = $this->getOption(self::OPTION_TRANSACTIONAL_ENTITIES);
        foreach ($data as $key => &$entity) {
            if ($entity === $object) {
                unset($data[$key]);
            }
        }
        $this->setOption(self::OPTION_TRANSACTIONAL_ENTITIES, $data);

        return $this;
    }

    /**
     * Clears all transactional entities.
     */
    public function clearTransactionalEntities(): self
    {
        $this->setOption(self::OPTION_TRANSACTIONAL_ENTITIES, []);

        return $this;
    }

    /**
     * Sets a callback to be executed on the first iteration.
     *
     * @param callable|null $callable a callable that receives the first BatchIteration
     */
    public function onFirstIteration(?callable $callable = null): self
    {
        $this->setOption(self::OPTION_ON_FIRST_ITERATION_CALLABLE, $callable);

        return $this;
    }

    /**
     * Sets a callback to be executed on flush.
     *
     * @param callable|null $callable a callable that receives the BatchProcess and an optional BatchIteration
     */
    public function onFlush(?callable $callable = null): self
    {
        $this->setOption(self::OPTION_ON_FLUSH_CALLABLE, $callable);

        return $this;
    }

    /**
     * Sets a callback to be executed on rollback.
     *
     * @param callable|null $callable a callable that receives the BatchProcess, the exception, and an optional BatchIteration
     */
    public function rollback(?callable $callable = null): self
    {
        $this->setOption(self::OPTION_ROLLBACK_CALLABLE, $callable);

        return $this;
    }

    /**
     * Retrieves the Doctrine EntityManager.
     *
     * @return EntityManagerInterface the Doctrine EntityManager
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Retrieves the batch iterator.
     *
     * @return BatchIteratorInterface the iterator for processing batch iterations
     */
    public function getIterator(): BatchIteratorInterface
    {
        return $this->iterator;
    }

    /**
     * Retrieves the batch handler.
     *
     * @return BatchHandlerInterface|null the batch handler, if one is set
     */
    public function getHandler(): ?BatchHandlerInterface
    {
        return $this->handler;
    }

    /**
     * Retrieves the configured buffer size.
     *
     * @return int the number of iterations before flushing
     */
    public function getBufferSize(): int
    {
        $bufferSize = $this->getOption(self::OPTION_BUFFER_SIZE);

        return \is_int($bufferSize) ? $bufferSize : self::DEFAULT_BUFFER_SIZE;
    }

    /**
     * Retrieves the total runtime of the batch process.
     *
     * @return float the runtime in seconds
     */
    public function getRunTime(): float
    {
        return $this->runTime;
    }

    /**
     * Executes the batch process.
     *
     * Iterates over the batch iterator and processes each iteration using the configured handler.
     * The method tracks the process runtime, applies disabled ID generators, and handles rollbacks on failure.
     *
     * @return int the number of iterations achieved
     *
     * @throws ProcessExceptionInterface if the process fails
     */
    public function execute(): int
    {
        [$count, $this->runTime, $microTime] = [0, 0.00, microtime(true)];

        /** @var array<string> $configuredDisabledIdGenerators */
        $configuredDisabledIdGenerators = $this->getOption(self::OPTION_DISABLED_ID_GENERATORS);
        $disabledIdGenerators = [];
        foreach ($configuredDisabledIdGenerators as $entityFqcn) {
            $metadata = $this->entityManager->getClassMetaData($entityFqcn);
            $disabledIdGenerators[$entityFqcn]['generator_type'] = $metadata->generatorType;
            $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
            $disabledIdGenerators[$entityFqcn]['id_generator'] = $metadata->idGenerator;
            $metadata->setIdGenerator(new AssignedGenerator());
        }

        $iterator = $this->iterate();

        try {
            foreach ($iterator as $batchIteration) {
                if (0 === $count && \is_callable($firstIterationCallable = $this->getOption(self::OPTION_ON_FIRST_ITERATION_CALLABLE))) {
                    $firstIterationCallable($batchIteration);
                }
                if ($handler = $this->handler) {
                    $handler->__invoke($batchIteration);
                }
                ++$count;
            }
        } catch (\Throwable $exception) {
            if (\is_callable($rollbackCallable = $this->getOption(self::OPTION_ROLLBACK_CALLABLE))) {
                try {
                    $rollbackCallable($this, $exception, $batchIteration ?? null);
                } catch (\Throwable $exception) {
                    throw new RollbackFailureException('Failed to rollback due to process failure.', 0, $exception);
                }
            } else {
                throw new ProcessException(\sprintf('Batch process failed at iteration #%d.', $count), 0, $exception);
            }
        }

        foreach ($disabledIdGenerators as $entityFqcn => $params) {
            $metadata = $this->entityManager->getClassMetaData((string) $entityFqcn);
            $metadata->setIdGeneratorType($params['generator_type']);
            $metadata->setIdGenerator($params['id_generator']);
        }

        $this->runTime = microtime(true) - $microTime;

        /* @var int $count */
        return $iterator->getReturn();
    }

    /**
     * Iterates over the batch process data.
     *
     * Clears any configured middlewares, sets up the EntityManager for the iterator,
     * and yields BatchIteration instances for each data item. Flushes the EntityManager
     * periodically based on the buffer size. If the "dry_run" option is enabled, flush() is not called.
     *
     * @return \Generator<int, BatchIteration, mixed, int> a generator yielding BatchIteration objects
     */
    public function iterate(): \Generator
    {
        $this->removeMiddlewares();
        $this->iterator->setEntityManager($this->entityManager);
        [$count, $this->runTime, $microTime] = [0, 0.00, microtime(true)];

        foreach ($this->iterator as $data) {
            /** @var BatchIteration $batchIteration */
            $batchIteration = new BatchIteration($this, $data, $count);
            yield $count => $batchIteration;

            if ($count > 1 && 0 === ($count % $this->getBufferSize())) {
                $this->flush($batchIteration);
            }
            ++$count;
        }

        $this->flush();
        $this->restoreMiddlewares();
        $this->runTime = microtime(true) - $microTime;

        return $count;
    }

    /**
     * Removes configured middlewares from the EntityManager.
     *
     * @internal
     */
    private function removeMiddlewares(): void
    {
        $this->middlewares = $this->entityManager->getConfiguration()->getMiddlewares();
        $this->entityManager->getConfiguration()->setMiddlewares([]);
    }

    /**
     * Restores the previously removed middlewares to the EntityManager.
     *
     * @internal
     */
    private function restoreMiddlewares(): void
    {
        $this->entityManager->getConfiguration()->setMiddlewares($this->middlewares);
    }

    /**
     * Flushes the EntityManager and clears its context.
     *
     * Reloads any transactional entities and executes the onFlush callable if configured.
     * If the "dry_run" option is enabled, no flush is performed.
     *
     * @internal
     *
     * @param BatchIteration|null $iteration the current batch iteration, if applicable
     */
    private function flush(?BatchIteration $iteration = null): void
    {
        if (true !== $this->getOption(self::OPTION_DRY_RUN)) {
            $this->entityManager->flush();
        }
        $this->entityManager->clear();

        /** @var array<object> $transactionalEntities */
        $transactionalEntities = $this->getOption(self::OPTION_TRANSACTIONAL_ENTITIES);
        foreach ($transactionalEntities as &$entity) {
            $classMetadata = $this->entityManager->getClassMetaData($entity::class);
            $idProperty = $classMetadata->getSingleIdReflectionProperty();
            if ($idProperty) {
                $entity = $this->entityManager->find($classMetadata->getName(), $idProperty->getValue($entity));
            }
        }

        if (\is_callable($onFlush = $this->getOption(self::OPTION_ON_FLUSH_CALLABLE))) {
            $onFlush($this, $iteration);
        }
    }
}
