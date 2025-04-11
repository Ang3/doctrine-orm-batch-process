<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Handler;

use Ang3\Doctrine\ORM\Batch\BatchIteration;

/**
 * PersistEntityHandler is responsible for persisting an entity during a batch iteration.
 *
 * It allows configuration of pre- and post-persistence callbacks, and provides options
 * to skip insertions or updates based on whether the entity already exists in the persistence context.
 */
final class PersistEntityHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    /**
     * Option key to skip insertions when the entity is not present.
     *
     * @var string
     */
    private const OPTION_SKIP_INSERTIONS = 'skip_insertions';

    /**
     * Option key to skip updates when the entity already exists.
     *
     * @var string
     */
    private const OPTION_SKIP_UPDATES = 'skip_updates';

    /**
     * Option key for the callable to execute when an entity is skipped.
     *
     * @var string
     */
    private const OPTION_ON_SKIPPED_CALLABLE = 'on_skipped_callable';

    /**
     * Option key for the callable to execute before persisting the entity.
     *
     * @var string
     */
    private const OPTION_PRE_PERSIST_CALLABLE = 'pre_persist_callable';

    /**
     * Option key for the callable to execute after persisting the entity.
     *
     * @var string
     */
    private const OPTION_POST_PERSIST_CALLABLE = 'post_persist_callable';

    public static function new(): self
    {
        return (new self())
            ->setOption(self::OPTION_SKIP_INSERTIONS, false)
            ->setOption(self::OPTION_SKIP_UPDATES, false)
        ;
    }

    public function __invoke(BatchIteration $iteration): void
    {
        $entity = $iteration->getData();

        if (!\is_object($entity)) {
            throw new \InvalidArgumentException(\sprintf('Expected data of type "object", got "%s".', \gettype($entity)));
        }

        [$skipInsertions, $skipUpdates] = [
            true === $this->getOption(self::OPTION_SKIP_INSERTIONS),
            true === $this->getOption(self::OPTION_SKIP_UPDATES),
        ];

        if ($skipInsertions || $skipUpdates) {
            $onSkippedCallable = $this->getOption(self::OPTION_ON_SKIPPED_CALLABLE);
            $exists = $iteration->getEntityManager()->contains($entity);

            if (!$exists && $skipInsertions) {
                if (\is_callable($onSkippedCallable)) {
                    $onSkippedCallable($entity, $iteration);
                }

                return;
            }

            if ($exists && $skipUpdates) {
                if (\is_callable($onSkippedCallable)) {
                    $onSkippedCallable($entity, $iteration);
                }

                return;
            }
        }

        if (\is_callable($prePersist = $this->getOption(self::OPTION_PRE_PERSIST_CALLABLE))) {
            $prePersist($entity, $iteration);
        }

        $iteration->getEntityManager()->persist($entity);

        if (\is_callable($postPersist = $this->getOption(self::OPTION_POST_PERSIST_CALLABLE))) {
            $postPersist($entity, $iteration);
        }
    }

    /**
     * Configures the handler to skip insertions for non-existing entities.
     *
     * @return self returns the current instance for method chaining
     */
    public function skipInsertions(): self
    {
        $this->setOption(self::OPTION_SKIP_INSERTIONS, true);

        return $this;
    }

    /**
     * Configures the handler to skip updates for existing entities.
     *
     * @return self returns the current instance for method chaining
     */
    public function skipUpdates(): self
    {
        $this->setOption(self::OPTION_SKIP_UPDATES, true);

        return $this;
    }

    /**
     * Sets a callback to be executed when an entity is skipped.
     *
     * This callable is invoked when the handler decides to skip an entity due to the
     * configured insertion or update skip options.
     *
     * @param callable $callable a callable that accepts the skipped entity and the BatchIteration
     *
     * @return self returns the current instance for method chaining
     */
    public function onSkippedEntity(callable $callable): self
    {
        $this->setOption(self::OPTION_ON_SKIPPED_CALLABLE, $callable);

        return $this;
    }

    /**
     * Sets a pre-persist callback to be executed before persisting an entity.
     *
     * The provided callable, if not null, is called before the entity is persisted.
     *
     * @param callable|null $callable a callable that accepts the entity and the BatchIteration
     *
     * @return self returns the current instance for method chaining
     */
    public function onPrePersist(?callable $callable = null): self
    {
        $this->setOption(self::OPTION_PRE_PERSIST_CALLABLE, $callable);

        return $this;
    }

    /**
     * Sets a post-persist callback to be executed after persisting an entity.
     *
     * The provided callable, if not null, is called after the entity is persisted.
     *
     * @param callable|null $callable a callable that accepts the entity and the BatchIteration
     *
     * @return self returns the current instance for method chaining
     */
    public function onPostPersist(?callable $callable = null): self
    {
        $this->setOption(self::OPTION_POST_PERSIST_CALLABLE, $callable);

        return $this;
    }
}
