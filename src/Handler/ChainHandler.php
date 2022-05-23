<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Handler;

use Ang3\Doctrine\ORM\BatchProcess\ProcessIteration;
use Countable;
use Generator;
use IteratorAggregate;

final class ChainHandler implements ProcessHandlerInterface, IteratorAggregate, Countable
{
    use ProcessHandlerTrait;

    /**
     * @var ProcessHandlerInterface[]
     */
    private array $handlers = [];

    /**
     * @param ProcessHandlerInterface[] $handlers
     */
    public function __construct(array $handlers = [])
    {
        foreach ($handlers as $handler) {
            $this->append($handler);
        }
    }

    public function __invoke(ProcessIteration $iteration): void
    {
        foreach ($this->handlers as $handler) {
            $handler($iteration);
        }
    }

    /**
     * @param ProcessHandlerInterface[] $handlers
     */
    public static function new(array $handlers = []): self
    {
        return new self($handlers);
    }

    /**
     * @return Generator|ProcessHandlerInterface[]
     */
    public function getIterator(): Generator
    {
        foreach ($this->handlers as $handler) {
            yield $handler;
        }
    }

    public function prepend(ProcessHandlerInterface $handler): self
    {
        if (!in_array($handler, $this->handlers, true)) {
            array_unshift($this->handlers, $handler);
        }

        return $this;
    }

    public function append(ProcessHandlerInterface $handler): self
    {
        if (!in_array($handler, $this->handlers, true)) {
            $this->handlers[] = $handler;
        }

        return $this;
    }

    public function remove(ProcessHandlerInterface $handler): self
    {
        $key = array_search($handler, $this->handlers, true);

        if (false !== $key) {
            unset($this->handlers[$key]);
        }

        return $this;
    }

    public function first(): ?ProcessHandlerInterface
    {
        reset($this->handlers);

        return $this->handlers[0] ?? null;
    }

    public function last(): ?ProcessHandlerInterface
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
        return count($this->handlers);
    }
}
