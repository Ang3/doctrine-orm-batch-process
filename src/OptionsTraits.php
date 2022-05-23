<?php

namespace Ang3\Doctrine\ORM\BatchProcess;

trait OptionsTraits
{
    private ?OptionsBag $options = null;

    /**
     * @param mixed|null $value
     */
    protected function setOption(string $name, $value): static
    {
        $this
            ->getOptions()
            ->set($name, $value);

        return $this;
    }

    /**
     * @return mixed|null
     */
    protected function getOption(string $name): mixed
    {
        return $this
            ->getOptions()
            ->get($name);
    }

    protected function getOptions(): OptionsBag
    {
        if (!$this->options) {
            $this->options = new OptionsBag();
        }

        return $this->options;
    }
}
