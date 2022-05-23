<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Iterator;

use Doctrine\ORM\EntityManagerInterface;
use Generator;
use IteratorAggregate;

interface ProcessIteratorInterface extends IteratorAggregate
{
    public function setEntityManager(EntityManagerInterface $entityManager): void;

    public function getIterator(): Generator;
}
