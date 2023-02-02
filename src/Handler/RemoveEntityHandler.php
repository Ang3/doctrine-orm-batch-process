<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Handler;

use Ang3\Doctrine\ORM\Batch\BatchIteration;

final class RemoveEntityHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    /**
     * Handler options.
     *
     * @internal
     */
    private const OPTION_PRE_REMOVE_CALLABLE = 'pre_remove_callable';
    private const OPTION_POST_REMOVE_CALLABLE = 'post_remove_callable';

    public function __invoke(BatchIteration $iteration): void
    {
        $entity = $iteration->getData();

        if (!\is_object($entity)) {
            throw new \InvalidArgumentException(sprintf('Expected data of type "object", got "%s".', \gettype($entity)));
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

    public function onPreRemove(callable $callable = null): self
    {
        $this->setOption(self::OPTION_PRE_REMOVE_CALLABLE, $callable);

        return $this;
    }

    public function onPostRemove(callable $callable = null): self
    {
        $this->setOption(self::OPTION_POST_REMOVE_CALLABLE, $callable);

        return $this;
    }
}
