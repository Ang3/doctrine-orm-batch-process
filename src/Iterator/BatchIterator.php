<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

class BatchIterator implements BatchIteratorInterface
{
    use ProcessIteratorTrait;

    public function __construct(private iterable $iterator)
    {
    }

    public static function new(iterable $iterator = null): self
    {
        return new self($iterator ?: []);
    }

    public function getIterator(): \Generator
    {
        yield from $this->iterator;
    }
}
