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
 * RemoveEntityHandler is a batch handler responsible for removing entities
 * from the persistence context.
 *
 * This handler retrieves an entity from the BatchIteration, executes optional
 * pre-remove and post-remove callbacks, and then removes the entity using the
 * associated EntityManager.
 */
final class RemoveEntityHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    /**
     * Option key for the pre-remove callable.
     *
     * This option holds a callable that is executed before the entity removal.
     *
     * @internal
     *
     * @var string
     */
    private const OPTION_PRE_REMOVE_CALLABLE = 'pre_remove_callable';

    /**
     * Option key for the post-remove callable.
     *
     * This option holds a callable that is executed after the entity removal.
     *
     * @internal
     *
     * @var string
     */
    private const OPTION_POST_REMOVE_CALLABLE = 'post_remove_callable';

    public function __invoke(BatchIteration $iteration): void
    {
        $entity = $iteration->getData();

        if (!\is_object($entity)) {
            throw new \InvalidArgumentException(\sprintf('Expected data of type "object", got "%s".', \gettype($entity)));
        }

        if (\is_callable($preRemove = $this->getOption(self::OPTION_PRE_REMOVE_CALLABLE))) {
            $preRemove($entity, $iteration);
        }

        $iteration
            ->getEntityManager()
            ->remove($entity)
        ;

        if (\is_callable($postRemove = $this->getOption(self::OPTION_POST_REMOVE_CALLABLE))) {
            $postRemove($entity, $iteration);
        }
    }

    /**
     * Sets the pre-remove callable.
     *
     * This callable is executed before the entity removal is performed.
     *
     * @param callable|null $callable a callable that accepts the entity and the BatchIteration
     *
     * @return self returns the current instance for method chaining
     */
    public function onPreRemove(?callable $callable = null): self
    {
        $this->setOption(self::OPTION_PRE_REMOVE_CALLABLE, $callable);

        return $this;
    }

    /**
     * Sets the post-remove callable.
     *
     * This callable is executed after the entity has been removed.
     *
     * @param callable|null $callable a callable that accepts the entity and the BatchIteration
     *
     * @return self returns the current instance for method chaining
     */
    public function onPostRemove(?callable $callable = null): self
    {
        $this->setOption(self::OPTION_POST_REMOVE_CALLABLE, $callable);

        return $this;
    }
}
