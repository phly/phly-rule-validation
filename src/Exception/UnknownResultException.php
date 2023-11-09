<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Exception;

use RuntimeException;

use function sprintf;

class UnknownResultException extends RuntimeException implements ValidationException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf(
            'No validation result exists for key "%s"',
            $key
        ), 500);
    }
}
