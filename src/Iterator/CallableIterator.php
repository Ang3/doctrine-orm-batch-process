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
class CallableIterator implements BatchIteratorInterface
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

    /**
     * @return self<TKey, TValue>
     */
    public static function new(callable $callable): self
    {
        return new self($callable);
    }

    /**
     * @return \Generator<TValue>
     */
    public function getIterator(): \Generator
    {
        $callable = $this->callable;

        foreach ($callable() as $key => $value) {
            yield $key => $value;
        }
    }
}
