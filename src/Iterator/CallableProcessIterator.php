<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Iterator;

use Generator;

class CallableProcessIterator implements ProcessIteratorInterface
{
    use ProcessIteratorTrait;

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

    /**
     * @return Generator|mixed[]
     */
    public function getIterator(): Generator
    {
        $callable = $this->callable;

        foreach ($callable() as $value) {
            yield $value;
        }
    }
}
