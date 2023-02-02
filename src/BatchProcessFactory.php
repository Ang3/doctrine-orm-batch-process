<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class BatchProcessFactory
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function iterateData(iterable|callable $data): BatchProcess
    {
        return BatchProcess::iterateData($this->entityManager, $data);
    }

    /**
     * @param class-string $entityFqcn
     */
    public function iterateEntities(string $entityFqcn, array $identifiers): BatchProcess
    {
        return BatchProcess::iterateEntities($this->entityManager, $entityFqcn, $identifiers);
    }

    public function iterateQueryResult(Query|QueryBuilder $query): BatchProcess
    {
        return BatchProcess::iterateQueryResult($query);
    }
}
