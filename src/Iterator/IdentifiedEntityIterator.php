<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Iterator;

use Generator;

class IdentifiedEntityIterator implements ProcessIteratorInterface
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
     * @return Generator|object[]
     */
    public function getIterator(): Generator
    {
        foreach ($this->identifiers as $id) {
            $entity = $this->entityManager->find($this->entityFqcn, $id);

            if ($entity) {
                yield $entity;
            }
        }
    }
}
