<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

use Doctrine\ORM\EntityManagerInterface;

/**
 * @template TKey of array-key
 * @template TValue of mixed
 *
 * @extends \IteratorAggregate<TKey, TValue>
 */
interface BatchIteratorInterface extends \IteratorAggregate
{
    /**
     * Sets the Doctrine Entity Manager.
     *
     * This method injects the EntityManager instance used for accessing
     * and managing entities.
     *
     * @param EntityManagerInterface $entityManager the Doctrine Entity Manager instance
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void;

    /**
     * @return \Generator<TValue>
     */
    public function getIterator(): \Generator;
}
