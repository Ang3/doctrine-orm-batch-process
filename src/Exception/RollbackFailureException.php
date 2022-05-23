<?php

namespace Ang3\Doctrine\ORM\BatchProcess\Exception;

use RuntimeException;

class RollbackFailureException extends RuntimeException implements ProcessExceptionInterface
{
}
