<?php

namespace Ang3\Doctrine\ORM\BatchProcess;

use Ang3\Doctrine\ORM\BatchProcess\Iterator\ProcessIteratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class BatchProcessFactory
{
    /**
     * @param class-string $entityFqcn
     */
    public function createFromEntityIdentifiers(EntityManagerInterface $entityManager, string $entityFqcn, array $identifiers): BatchProcess
    {
        return BatchProcess::fromEntityIdentifiers($entityManager, $entityFqcn, $identifiers);
    }

    public function createFromQueryBuilderResult(QueryBuilder $queryBuilder): BatchProcess
    {
        return BatchProcess::fromQueryBuilderResult($queryBuilder);
    }

    public function createFromQuery(Query $query): BatchProcess
    {
        return BatchProcess::fromOrmQuery($query);
    }

    public function createFromCallable(EntityManagerInterface $entityManager, callable $callback): BatchProcess
    {
        return BatchProcess::fromCallable($entityManager, $callback);
    }

    public function createFromIterable(EntityManagerInterface $entityManager, iterable $data): BatchProcess
    {
        return BatchProcess::fromIterable($entityManager, $data);
    }

    public function create(EntityManagerInterface $entityManager, ProcessIteratorInterface $iterator): BatchProcess
    {
        return BatchProcess::create($entityManager, $iterator);
    }
}
