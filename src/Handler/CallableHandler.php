<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Handler;

use Ang3\Doctrine\ORM\Batch\BatchIteration;

final class CallableHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

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

    public function __invoke(BatchIteration $iteration): void
    {
        $callable = $this->callable;
        $callable($iteration->getData(), $iteration);
    }
}
