<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Handler;

use Ang3\Doctrine\ORM\Batch\BatchIteration;

final class ChainHandler implements BatchHandlerInterface, \IteratorAggregate, \Countable
{
    use BatchHandlerTrait;

    /**
     * @var BatchHandlerInterface[]
     */
    private array $handlers = [];

    /**
     * @param BatchHandlerInterface[] $handlers
     */
    public function __construct(array $handlers = [])
    {
        foreach ($handlers as $handler) {
            $this->append($handler);
        }
    }

    public function __invoke(BatchIteration $iteration): void
    {
        foreach ($this->handlers as $handler) {
            $handler($iteration);
        }
    }

    /**
     * @param BatchHandlerInterface[] $handlers
     */
    public static function new(array $handlers = []): self
    {
        return new self($handlers);
    }

    /**
     * @return \Generator|BatchHandlerInterface[]
     */
    public function getIterator(): \Generator
    {
        foreach ($this->handlers as $handler) {
            yield $handler;
        }
    }

    public function prepend(BatchHandlerInterface $handler): self
    {
        if (!\in_array($handler, $this->handlers, true)) {
            array_unshift($this->handlers, $handler);
        }

        return $this;
    }

    public function append(BatchHandlerInterface $handler): self
    {
        if (!\in_array($handler, $this->handlers, true)) {
            $this->handlers[] = $handler;
        }

        return $this;
    }

    public function remove(BatchHandlerInterface $handler): self
    {
        $key = array_search($handler, $this->handlers, true);

        if (false !== $key) {
            unset($this->handlers[$key]);
        }

        return $this;
    }

    public function first(): ?BatchHandlerInterface
    {
        reset($this->handlers);

        return $this->handlers[0] ?? null;
    }

    public function last(): ?BatchHandlerInterface
    {
        $handlers = array_reverse($this->handlers);
        reset($this->handlers);

        return $handlers[0] ?? null;
    }

    public function clear(): self
    {
        $this->handlers = [];

        return $this;
    }

    public function count(): int
    {
        return \count($this->handlers);
    }
}
