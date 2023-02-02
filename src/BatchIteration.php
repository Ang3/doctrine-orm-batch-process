<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch;

use Ang3\Doctrine\ORM\Batch\Iterator\BatchIteratorInterface;
use Doctrine\ORM\EntityManagerInterface;

class BatchIteration
{
    public function __construct(
        private readonly BatchProcess $process,
        private readonly mixed $data,
        private readonly int $position
    ) {
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

    public function getIterator(): BatchIteratorInterface
    {
        return $this->process->getIterator();
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->process->getEntityManager();
    }
}
