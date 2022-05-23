<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Handler;

use Ang3\Doctrine\ORM\BatchProcess\ProcessIteration;

final class CallableHandler implements ProcessHandlerInterface
{
    use ProcessHandlerTrait;

    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public static function new(callable $callable): self
    {
        return new self($callable);
    }

    public function __invoke(ProcessIteration $iteration): void
    {
        $callable = $this->callable;
        $callable($iteration->getData(), $iteration);
    }
}
