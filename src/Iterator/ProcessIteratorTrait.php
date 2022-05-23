<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Iterator;

use Ang3\Doctrine\ORM\BatchProcess\OptionsTraits;
use Doctrine\ORM\EntityManagerInterface;

trait ProcessIteratorTrait
{
    use OptionsTraits;

    protected EntityManagerInterface $entityManager;

    public static function new(): self
    {
        return new static();
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }
}
