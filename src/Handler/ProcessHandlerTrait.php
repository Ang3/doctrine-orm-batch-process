<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Handler;

use Ang3\Doctrine\ORM\BatchProcess\OptionsTraits;

trait ProcessHandlerTrait
{
    use OptionsTraits;

    public static function new(): static
    {
        return new static();
    }
}
