<?php

declare(strict_types=1);

/*
 * This file is part of package ang3/php-doctrine-orm-batch
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Ang3\Doctrine\ORM\Batch\Handler;

use Ang3\Doctrine\ORM\Batch\OptionsTraits;

trait BatchHandlerTrait
{
    use OptionsTraits;

    public static function new(): static
    {
        return new static();
    }
}
