<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Handler;

use Ang3\Doctrine\ORM\BatchProcess\ProcessIteration;
use InvalidArgumentException;

final class RemoveEntityHandler implements ProcessHandlerInterface
{
    use ProcessHandlerTrait;

    /**
     * Handler options.
     *
     * @internal
     */
    private const OPTION_PRE_REMOVE_CALLABLE = 'pre_remove_callable';
    private const OPTION_POST_REMOVE_CALLABLE = 'post_remove_callable';

    public function __invoke(ProcessIteration $iteration): void
    {
        $entity = $iteration->getData();

        if (!is_object($entity)) {
            throw new InvalidArgumentException(sprintf('Expected data of type "object", got "%s".', gettype($entity)));
        }

        if (is_callable($preRemove = $this->getOption(self::OPTION_PRE_REMOVE_CALLABLE))) {
            $preRemove($entity, $iteration);
        }

        $iteration
            ->getEntityManager()
            ->persist($entity);

        if (is_callable($postRemove = $this->getOption(self::OPTION_POST_REMOVE_CALLABLE))) {
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
