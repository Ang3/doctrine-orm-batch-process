<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

use Ang3\Doctrine\ORM\Batch\OptionsTraits;
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
