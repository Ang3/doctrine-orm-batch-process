<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Iterator;

use Doctrine\ORM\Query;
use Generator;

/**
 * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/batch-processing.html#iterating-large-results-for-data-processing
 */
class OrmQueryProcessIterator implements ProcessIteratorInterface
{
    use ProcessIteratorTrait;

    public function __construct(private Query $query)
    {
    }

    public static function new(Query $query): self
    {
        return new self($query);
    }

    public function getIterator(): Generator
    {
        foreach ($this->query->toIterable() as $row) {
            yield $row;
        }
    }
}
