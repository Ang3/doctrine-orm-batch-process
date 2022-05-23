<?php

namespace Ang3\Doctrine\ORM\BatchProcess;

use Ang3\Doctrine\ORM\BatchProcess\Iterator\ProcessIteratorInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProcessIteration
{
    public function __construct(private BatchProcess $process,
                                private mixed $data,
                                private int $position)
    {
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getBufferSize(): int
    {
        return $this->process->getBufferSize();
    }

    public function getIterator(): ProcessIteratorInterface
    {
        return $this->process->getIterator();
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->process->getEntityManager();
    }
}
