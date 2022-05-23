<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Iterator;

use Generator;

class ProcessIterator implements ProcessIteratorInterface
{
    use ProcessIteratorTrait;

    public function __construct(private iterable $iterator)
    {
    }

    public static function new(iterable $iterator = null): self
    {
        return new self($iterator ?: []);
    }

    public function getIterator(): Generator
    {
        yield from $this->iterator;
    }
}
