<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Handler;

use Ang3\Doctrine\ORM\BatchProcess\ProcessIteration;

interface ProcessHandlerInterface
{
    public function __invoke(ProcessIteration $iteration): void;
}
