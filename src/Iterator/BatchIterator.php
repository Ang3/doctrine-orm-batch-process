<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

/**
 * @template TKey of array-key
 * @template TValue of mixed
 *
 * @implements BatchIteratorInterface<TKey, TValue>
 */
class BatchIterator implements BatchIteratorInterface
{
    use ProcessIteratorTrait;

    /**
     * @param iterable<TKey, TValue> $iterator
     */
    public function __construct(private readonly iterable $iterator)
    {
    }

    /**
     * @param iterable<TKey, TValue>|null $iterator
     *
     * @return self<TKey, TValue>
     */
    public static function new(?iterable $iterator = null): self
    {
        return new self($iterator ?: []);
    }

    /**
     * @return \Generator<TValue>
     */
    public function getIterator(): \Generator
    {
        yield from $this->iterator;
    }
}
