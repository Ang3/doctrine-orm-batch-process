<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Handler;

use Ang3\Doctrine\ORM\BatchProcess\ProcessIteration;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class PersistEntityHandler implements ProcessHandlerInterface
{
    use ProcessHandlerTrait;

    /**
     * Handler options.
     *
     * @internal
     */
    private const OPTION_SKIP_INSERTIONS = 'skip_insertions';
    private const OPTION_SKIP_UPDATES = 'skip_updates';
    private const OPTION_ON_SKIPPED_CALLABLE = 'on_skipped_callable';
    private const OPTION_PRE_PERSIST_CALLABLE = 'pre_persist_callable';
    private const OPTION_POST_PERSIST_CALLABLE = 'post_persist_callable';

    public static function new(): self
    {
        return (new self())
            ->setOption(self::OPTION_SKIP_INSERTIONS, false)
            ->setOption(self::OPTION_SKIP_UPDATES, false);
    }

    public function __invoke(ProcessIteration $iteration): void
    {
        $entity = $iteration->getData();

        if (!is_object($entity)) {
            throw new InvalidArgumentException(sprintf('Expected data of type "object", got "%s".', gettype($entity)));
        }

        [$skipInsertions, $skipUpdates] = [
            true === $this->getOption(self::OPTION_SKIP_INSERTIONS),
            true === $this->getOption(self::OPTION_SKIP_UPDATES),
        ];

        if ($skipInsertions || $skipUpdates) {
            $identifiers = $this->getEntityIdentifiers($iteration->getEntityManager(), $entity);

            if ($identifiers) {
                $onSkippedCallable = $this->getOption(self::OPTION_ON_SKIPPED_CALLABLE);

                $exists = null !== $iteration
                        ->getEntityManager()
                        ->find($entity::class, $identifiers);

                if (!$exists && $skipInsertions) {
                    if (is_callable($onSkippedCallable)) {
                        $onSkippedCallable($entity, $iteration);
                    }

                    return;
                }

                if ($exists && $skipUpdates) {
                    if (is_callable($onSkippedCallable)) {
                        $onSkippedCallable($entity, $iteration);
                    }

                    return;
                }
            }
        }

        if (is_callable($prePersist = $this->getOption(self::OPTION_PRE_PERSIST_CALLABLE))) {
            $prePersist($entity, $iteration);
        }

        $iteration
            ->getEntityManager()
            ->persist($entity);

        if (is_callable($postPersist = $this->getOption(self::OPTION_POST_PERSIST_CALLABLE))) {
            $postPersist($entity, $iteration);
        }
    }

    public function skipInsertions(): self
    {
        $this->setOption(self::OPTION_SKIP_INSERTIONS, true);

        return $this;
    }

    public function skipUpdates(): self
    {
        $this->setOption(self::OPTION_SKIP_UPDATES, true);

        return $this;
    }

    public function onSkippedEntity(callable $callable): self
    {
        $this->setOption(self::OPTION_ON_SKIPPED_CALLABLE, $callable);

        return $this;
    }

    public function onPrePersist(callable $callable = null): self
    {
        $this->setOption(self::OPTION_PRE_PERSIST_CALLABLE, $callable);

        return $this;
    }

    public function onPostPersist(callable $callable = null): self
    {
        $this->setOption(self::OPTION_POST_PERSIST_CALLABLE, $callable);

        return $this;
    }

    /**
     * @internal
     *
     * @return mixed[]
     */
    private function getEntityIdentifiers(EntityManagerInterface $entityManager, object $entity): array
    {
        $classMetadata = $entityManager->getClassMetadata($entity::class);

        if ($classMetadata->isIdentifierComposite) {
            return [];
        }

        return $classMetadata->getIdentifierValues($entity);
    }
}
