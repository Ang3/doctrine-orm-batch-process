<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch;

use Doctrine\ORM\EntityManagerInterface;

readonly class BatchIteration
{
    public function __construct(
        private BatchProcess $process,
        private mixed $data,
        private int $position
    ) {
    }

    /**
     * Retrieves the data for the current iteration.
     *
     * @return mixed the data associated with this iteration
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Retrieves the position index of the current iteration.
     *
     * @return int the position of this iteration
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Retrieves the buffer size configured in the batch process.
     *
     * @return int the buffer size used in the batch process
     */
    public function getBufferSize(): int
    {
        return $this->process->getBufferSize();
    }

    /**
     * Retrieves the Doctrine Entity Manager used in the batch process.
     *
     * @return EntityManagerInterface the Doctrine Entity Manager instance
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->process->getEntityManager();
    }
}
