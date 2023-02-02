<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Iterator;

class CallableIterator implements BatchIteratorInterface
{
    use ProcessIteratorTrait;

    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public static function new(callable $callable): self
    {
        return new self($callable);
    }

    /**
     * @return \Generator|mixed[]
     */
    public function getIterator(): \Generator
    {
        $callable = $this->callable;

        foreach ($callable() as $value) {
            yield $value;
        }
    }
}
