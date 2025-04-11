<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

/**
 * @template TKey of array-key
 * @template TValue of object
 *
 * @implements BatchIteratorInterface<TKey, TValue>
 */
class EntityIdentifiersIterator implements BatchIteratorInterface
{
    use ProcessIteratorTrait;

    /**
     * @param class-string $entityFqcn
     * @param mixed[]      $identifiers
     */
    public function __construct(
        private readonly string $entityFqcn,
        private readonly array $identifiers
    ) {
    }

    /**
     * @param class-string $entityFqcn
     * @param mixed[]      $identifiers
     *
     * @return self<TKey, object>
     */
    public static function new(string $entityFqcn, array $identifiers): self
    {
        return new self($entityFqcn, $identifiers);
    }

    /**
     * @return \Generator<object>
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
