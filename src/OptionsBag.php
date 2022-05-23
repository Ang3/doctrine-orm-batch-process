<?php

namespace Ang3\Doctrine\ORM\BatchProcess;

class OptionsBag implements \ArrayAccess, \Countable
{
    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    public function __construct(array $options = [])
    {
        $this->initialize($options);
    }

    /**
     * @param string $offset
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->options);
    }

    /**
     * @param string $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        if (array_key_exists($offset, $this->options)) {
            unset($this->options[$offset]);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function initialize(array $options = []): self
    {
        $this->options = [];

        foreach ($options as $name => $value) {
            $this->set($name, $value);
        }

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function set(string $name, $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function get(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    public function count(): int
    {
        return count($this->options);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->options;
    }
}
