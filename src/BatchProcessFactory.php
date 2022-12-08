<?php

namespace Ang3\Doctrine\ORM\BatchProcess;

use Ang3\Doctrine\ORM\BatchProcess\Iterator\ProcessIteratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class BatchProcessFactory
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param class-string $entityFqcn
     */
    public function createFromEntityIdentifiers(string $entityFqcn, array $identifiers): BatchProcess
    {
        return BatchProcess::fromEntityIdentifiers($this->entityManager, $entityFqcn, $identifiers);
    }

    public function createFromQueryBuilderResult(QueryBuilder $queryBuilder): BatchProcess
    {
        return BatchProcess::fromQueryBuilderResult($queryBuilder);
    }

    public function createFromQuery(Query $query): BatchProcess
    {
        return BatchProcess::fromOrmQuery($query);
    }

    public function createFromCallable(callable $callback): BatchProcess
    {
        return BatchProcess::fromCallable($this->entityManager, $callback);
    }

    public function createFromIterable(iterable $data): BatchProcess
    {
        return BatchProcess::fromIterable($this->entityManager, $data);
    }

    public function create(ProcessIteratorInterface $iterator): BatchProcess
    {
        return BatchProcess::create($this->entityManager, $iterator);
    }
}
