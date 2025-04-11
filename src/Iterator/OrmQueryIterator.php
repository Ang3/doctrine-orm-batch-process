<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

use Doctrine\ORM\Query;

/**
 * @template TKey of array-key
 * @template TValue of mixed
 *
 * @implements BatchIteratorInterface<TKey, TValue>
 */
class OrmQueryIterator implements BatchIteratorInterface
{
    use ProcessIteratorTrait;

    public function __construct(private readonly Query $query)
    {
    }

    /**
     * @return self<TKey, TValue>
     */
    public static function new(Query $query): self
    {
        return new self($query);
    }

    /**
     * @return \Generator<TValue>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->query->toIterable() as $row) {
            yield $row;
        }
    }
}
