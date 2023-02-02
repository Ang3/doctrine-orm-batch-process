<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

class EntityIdentifiersIterator implements BatchIteratorInterface
{
    use ProcessIteratorTrait;

    /**
     * @param class-string $entityFqcn
     */
    public function __construct(private string $entityFqcn, private array $identifiers)
    {
    }

    /**
     * @param class-string $entityFqcn
     */
    public static function new(string $entityFqcn, array $identifiers): self
    {
        return new self($entityFqcn, $identifiers);
    }

    /**
     * @return \Generator|object[]
     */
    public function getIterator(): \Generator
    {
        foreach ($this->identifiers as $id) {
            $entity = $this->entityManager->find($this->entityFqcn, $id);

            if ($entity) {
                yield $entity;
            }
        }
    }
}
