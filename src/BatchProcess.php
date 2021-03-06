<?php

namespace Ang3\Doctrine\ORM\BatchProcess;

use Ang3\Doctrine\ORM\BatchProcess\Exception\ProcessException;
use Ang3\Doctrine\ORM\BatchProcess\Exception\ProcessExceptionInterface;
use Ang3\Doctrine\ORM\BatchProcess\Exception\RollbackFailureException;
use Ang3\Doctrine\ORM\BatchProcess\Handler\ProcessHandlerInterface;
use Ang3\Doctrine\ORM\BatchProcess\Iterator\CallableProcessIterator;
use Ang3\Doctrine\ORM\BatchProcess\Iterator\IdentifiedEntityIterator;
use Ang3\Doctrine\ORM\BatchProcess\Iterator\OrmQueryProcessIterator;
use Ang3\Doctrine\ORM\BatchProcess\Iterator\ProcessIterator;
use Ang3\Doctrine\ORM\BatchProcess\Iterator\ProcessIteratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Generator;
use Throwable;

/**
 * A batch process for an entity manager.
 */
class BatchProcess
{
    use OptionsTraits;

    /**
     * Default options.
     */
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

    /**
     * Default options.
     *
     * @internal
     */
    private const DEFAULT_OPTIONS = [
        self::OPTION_BUFFER_SIZE => self::DEFAULT_BUFFER_SIZE,
        self::OPTION_DISABLED_ID_GENERATORS => [],
        self::OPTION_TRANSACTIONAL_ENTITIES => [],
        self::OPTION_ON_FIRST_ITERATION_CALLABLE => null,
        self::OPTION_ON_FLUSH_CALLABLE => null,
        self::OPTION_ROLLBACK_CALLABLE => null,
    ];
    private float $runTime = 0.00;

    public function __construct(private EntityManagerInterface $entityManager,
                                private ProcessIteratorInterface $iterator,
                                private ?ProcessHandlerInterface $handler = null,
                                ?int $bufferSize = null)
    {
        $this->options = new OptionsBag(self::DEFAULT_OPTIONS);
        $this->setBufferSize($bufferSize ?: self::DEFAULT_BUFFER_SIZE);
    }

    /**
     * @param class-string $entityFqcn
     */
    public static function fromEntityIdentifiers(EntityManagerInterface $entityManager, string $entityFqcn, array $identifiers): self
    {
        return self::create($entityManager, new IdentifiedEntityIterator($entityFqcn, $identifiers));
    }

    public static function fromQueryBuilderResult(QueryBuilder $queryBuilder): self
    {
        return self::fromOrmQuery($queryBuilder->getQuery());
    }

    public static function fromOrmQuery(Query $query): self
    {
        return self::create($query->getEntityManager(), new OrmQueryProcessIterator($query));
    }

    public static function fromCallable(EntityManagerInterface $entityManager, callable $callback): self
    {
        return self::create($entityManager, new CallableProcessIterator($callback));
    }

    public static function fromIterable(EntityManagerInterface $entityManager, iterable $data): self
    {
        return self::create($entityManager, new ProcessIterator($data));
    }

    public static function create(EntityManagerInterface $entityManager, ProcessIteratorInterface $iterator): self
    {
        return new self($entityManager, $iterator);
    }

    public function setIterator(ProcessIteratorInterface $iterator): self
    {
        $this->iterator = $iterator;

        return $this;
    }

    public function setHandler(?ProcessHandlerInterface $handler = null): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function setBufferSize(int $bufferSize = self::DEFAULT_BUFFER_SIZE): self
    {
        $this->setOption(self::OPTION_BUFFER_SIZE, $bufferSize);

        return $this;
    }

    public function disableIdGenerator(string ...$classNames): self
    {
        /** @var array $data */
        $data = $this->getOption(self::OPTION_DISABLED_ID_GENERATORS);

        foreach ($classNames as $className) {
            if (!in_array($className, $data, true)) {
                $data[] = $className;
            }
        }

        $this->setOption(self::OPTION_DISABLED_ID_GENERATORS, $data);

        return $this;
    }

    public function restoreIdGenerator(array $classNames): self
    {
        /** @var array $data */
        $data = $this->getOption(self::OPTION_DISABLED_ID_GENERATORS);

        foreach ($classNames as $className) {
            $key = array_search($className, $data);

            if (false !== $key) {
                unset($data[$key]);
            }
        }

        $this->setOption(self::OPTION_DISABLED_ID_GENERATORS, $data);

        return $this;
    }

    public function restoreAllIdGenerators(): self
    {
        $this->setOption(self::OPTION_DISABLED_ID_GENERATORS, []);

        return $this;
    }

    public function addTransactionalEntity(object &$object): self
    {
        /** @var array $data */
        $data = $this->getOption(self::OPTION_TRANSACTIONAL_ENTITIES);
        $data[] = &$object;
        $this->setOption(self::OPTION_TRANSACTIONAL_ENTITIES, $data);

        return $this;
    }

    public function removeTransactionalEntity(object &$object): self
    {
        /** @var array $data */
        $data = $this->getOption(self::OPTION_TRANSACTIONAL_ENTITIES);

        foreach ($data as $key => &$entity) {
            if ($entity === $object) {
                unset($data[$key]);
            }
        }

        $this->setOption(self::OPTION_TRANSACTIONAL_ENTITIES, $data);

        return $this;
    }

    public function clearTransactionalEntities(): self
    {
        $this->setOption(self::OPTION_TRANSACTIONAL_ENTITIES, []);

        return $this;
    }

    public function onFirstIteration(callable $callable = null): self
    {
        $this->setOption(self::OPTION_ON_FIRST_ITERATION_CALLABLE, $callable);

        return $this;
    }

    public function onFlush(callable $callable = null): self
    {
        $this->setOption(self::OPTION_ON_FLUSH_CALLABLE, $callable);

        return $this;
    }

    public function rollback(callable $callable = null): self
    {
        $this->setOption(self::OPTION_ROLLBACK_CALLABLE, $callable);

        return $this;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getIterator(): ProcessIteratorInterface
    {
        return $this->iterator;
    }

    public function getHandler(): ?ProcessHandlerInterface
    {
        return $this->handler;
    }

    public function getBufferSize(): int
    {
        /** @var int $bufferSize */
        $bufferSize = $this->getOption(self::OPTION_BUFFER_SIZE);

        return $bufferSize;
    }

    public function getRunTime(): float
    {
        return $this->runTime;
    }

    /**
     * @throws ProcessExceptionInterface on process failure
     *
     * @return int nb of iterations achieved
     */
    public function execute(): int
    {
        [$count, $this->runTime, $microTime] = [0, 0.00, microtime(true)];

        /** @var array $configuredDisabledIdGenerators */
        $configuredDisabledIdGenerators = $this->getOption(self::OPTION_DISABLED_ID_GENERATORS);
        $disabledIdGenerators = [];
        foreach ($configuredDisabledIdGenerators as $entityFqcn) {
            $metadata = $this->entityManager->getClassMetaData($entityFqcn);
            $disabledIdGenerators[$entityFqcn]['generator_type'] = $metadata->generatorType;
            $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
            $disabledIdGenerators[$entityFqcn]['id_generator'] = $metadata->idGenerator;
            $metadata->setIdGenerator(new AssignedGenerator());
        }

        try {
            foreach ($this->iterate() as $processIteration) {
                if (0 === $count && is_callable($firstIterationCallable = $this->getOption(self::OPTION_ON_FIRST_ITERATION_CALLABLE))) {
                    $firstIterationCallable($processIteration);
                }

                if ($handler = $this->handler) {
                    $handler->__invoke($processIteration);
                }

                ++$count;
            }
        } catch (Throwable $exception) {
            if (is_callable($rollbackCallable = $this->getOption(self::OPTION_ROLLBACK_CALLABLE))) {
                try {
                    $rollbackCallable($this, $exception);
                } catch (Throwable $exception) {
                    throw new RollbackFailureException('Failed to rollback due to process failure.', 0, $exception);
                }
            } else {
                throw new ProcessException(sprintf('Batch process failed at iteration #%d.', $count), 0, $exception);
            }
        }

        foreach ($disabledIdGenerators as $entityFqcn => $params) {
            $metadata = $this->entityManager->getClassMetaData((string) $entityFqcn);
            $metadata->setIdGeneratorType($params['generator_type']);
            $metadata->setIdGenerator($params['id_generator']);
        }

        $this->runTime = microtime(true) - $microTime;

        return $count;
    }

    /**
     * @return Generator|ProcessIteration[]
     */
    public function iterate(): Generator
    {
        [$count, $this->runTime, $microTime] = [0, 0.00, microtime(true)];
        $connection = $this->entityManager->getConnection();
        $configuration = $connection->getConfiguration();
        $sqlLogger = $configuration->getSQLLogger();
        $configuration->setSQLLogger();
        $this->iterator->setEntityManager($this->entityManager);

        foreach ($this->iterator as $data) {
            yield $count => $processIteration = new ProcessIteration($this, $data, $count);

            if ($count > 1 && 0 === ($count % $this->getBufferSize())) {
                $this->flush($processIteration);
            }

            ++$count;
        }

        $this->flush();
        $configuration->setSQLLogger($sqlLogger);
        $this->runTime = microtime(true) - $microTime;

        return $count;
    }

    /**
     * @internal
     */
    private function flush(?ProcessIteration $iteration = null): void
    {
        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var array $transactionalEntities */
        $transactionalEntities = $this->getOption(self::OPTION_TRANSACTIONAL_ENTITIES);

        // Reloading transactional entities
        foreach ($transactionalEntities as &$entity) {
            $classMetadata = $this->entityManager->getClassMetadata($entity::class);
            $idProperty = $classMetadata->getSingleIdReflectionProperty();
            $entity = $this->entityManager->find($classMetadata->getName(), $idProperty->getValue($entity));
        }

        if (is_callable($onFlush = $this->getOption(self::OPTION_ON_FLUSH_CALLABLE))) {
            $onFlush($this, $iteration);
        }
    }
}
