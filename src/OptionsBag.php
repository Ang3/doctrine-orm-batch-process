<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch;

/**
 * Class OptionsBag.
 *
 * A container for storing options with array-access and countable capabilities.
 *
 * This class allows you to manage an associative array of options,
 * enforcing that all keys are strings.
 *
 * @implements \ArrayAccess<string, mixed>
 */
class OptionsBag implements \ArrayAccess, \Countable
{
    /**
     * Internal storage for options.
     *
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $options an optional associative array of options
     */
    public function __construct(array $options = [])
    {
        $this->initialize($options);
    }

    /**
     * Checks if the given offset exists.
     *
     * @see \ArrayAccess
     *
     * @param mixed $offset the key to check; must be a string
     *
     * @return bool true if the key exists, false otherwise
     *
     * @throws \InvalidArgumentException if the offset is not a string
     */
    public function offsetExists($offset): bool
    {
        if (!\is_string($offset)) {
            throw new \InvalidArgumentException(\sprintf('Offset must be a string, %s given', \gettype($offset)));
        }

        return \array_key_exists($offset, $this->options);
    }

    /**
     * Retrieves the value for the given offset.
     *
     * @see \ArrayAccess
     *
     * @param mixed $offset the key to retrieve; must be a string
     *
     * @return mixed the value at the given key, or null if it does not exist
     *
     * @throws \InvalidArgumentException if the offset is not a string
     */
    public function offsetGet($offset): mixed
    {
        if (!\is_string($offset)) {
            throw new \InvalidArgumentException(\sprintf('Offset must be a string, %s given', \gettype($offset)));
        }

        return $this->get($offset);
    }

    /**
     * Sets a value for the given offset.
     *
     * @see \ArrayAccess
     *
     * @param mixed $offset The key to set; must be a string. Using null is not allowed.
     * @param mixed $value  the value to associate with the key
     *
     * @throws \InvalidArgumentException if the offset is not a string
     */
    public function offsetSet($offset, $value): void
    {
        if (!\is_string($offset)) {
            throw new \InvalidArgumentException('Offset must be a string');
        }
        $this->set($offset, $value);
    }

    /**
     * Unsets the value at the given offset.
     *
     * @see \ArrayAccess
     *
     * @param mixed $offset the key to unset; must be a string
     *
     * @throws \InvalidArgumentException if the offset is not a string
     */
    public function offsetUnset($offset): void
    {
        if (!\is_string($offset)) {
            throw new \InvalidArgumentException(\sprintf('Offset must be a string, %s given', \gettype($offset)));
        }
        if (\array_key_exists($offset, $this->options)) {
            unset($this->options[$offset]);
        }
    }

    /**
     * Initializes the options bag with the provided options array.
     *
     * All keys in the provided array must be strings.
     *
     * @param array<string, mixed> $options an associative array of options
     *
     * @return self returns the current instance for method chaining
     *
     * @throws \InvalidArgumentException if any of the keys is not a string
     */
    public function initialize(array $options = []): self
    {
        $this->options = [];

        foreach ($options as $name => $value) {
            if (!\is_string($name)) {
                throw new \InvalidArgumentException('All option keys must be strings');
            }
            $this->set($name, $value);
        }

        return $this;
    }

    /**
     * Sets an option value by name.
     *
     * @param string $name  the option name
     * @param mixed  $value the value to set
     *
     * @return self returns the current instance for method chaining
     */
    public function set(string $name, $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Retrieves the value of an option by name.
     *
     * @param string $name the option name
     *
     * @return mixed the value of the option, or null if not set
     */
    public function get(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Returns the number of options stored.
     *
     * @see Countable
     *
     * @return int the count of options
     */
    public function count(): int
    {
        return \count($this->options);
    }

    /**
     * Returns the options as an associative array.
     *
     * @return array<string, mixed> the options array
     */
    public function toArray(): array
    {
        return $this->options;
    }
}
