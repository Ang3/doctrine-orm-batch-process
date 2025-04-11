<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

use Ang3\Doctrine\ORM\Batch\OptionsTraits;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Trait ProcessIteratorTrait.
 *
 * This trait provides common functionality for iterator classes that handle batch processing.
 * It includes methods for setting the Doctrine EntityManager, as well as additional options management
 * via the OptionsTraits.
 */
trait ProcessIteratorTrait
{
    use OptionsTraits;

    /**
     * The Doctrine EntityManager instance used for handling database operations such as entity retrieval and persistence.
     */
    protected EntityManagerInterface $entityManager;

    /**
     * Creates a new instance of the class using this trait.
     *
     * This static factory method instantiates a new object of the class that uses this trait
     * with late static binding.
     *
     * @return self a new instance of the class using this trait
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Sets the Doctrine EntityManager.
     *
     * This method assigns the provided EntityManager instance to be used in batch processing operations.
     *
     * @param EntityManagerInterface $entityManager the Doctrine EntityManager instance
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }
}
