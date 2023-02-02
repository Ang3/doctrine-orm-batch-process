<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

use Doctrine\ORM\Query;

/**
 * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/batch-processing.html#iterating-large-results-for-data-processing
 */
class OrmQueryIterator implements BatchIteratorInterface
{
    use ProcessIteratorTrait;

    public function __construct(private Query $query)
    {
    }

    public static function new(Query $query): self
    {
        return new self($query);
    }

    public function getIterator(): \Generator
    {
        foreach ($this->query->toIterable() as $row) {
            yield $row;
        }
    }
}
