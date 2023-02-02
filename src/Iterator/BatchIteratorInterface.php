<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

use Doctrine\ORM\EntityManagerInterface;

interface BatchIteratorInterface extends \IteratorAggregate
{
    public function setEntityManager(EntityManagerInterface $entityManager): void;

    public function getIterator(): \Generator;
}
