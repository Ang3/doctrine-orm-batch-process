<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/doctrine-orm-batch-process
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch;

trait OptionsTraits
{
    private ?OptionsBag $options = null;

    protected function setOption(string $name, mixed $value): static
    {
        $this
            ->getOptions()
            ->set($name, $value)
        ;

        return $this;
    }

    protected function getOption(string $name): mixed
    {
        return $this
            ->getOptions()
            ->get($name)
        ;
    }

    protected function getOptions(): OptionsBag
    {
        if (!$this->options) {
            $this->options = new OptionsBag();
        }

        return $this->options;
    }
}
