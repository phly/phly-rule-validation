<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Exception;

use RuntimeException;

use function sprintf;

class DuplicateRuleKeyException extends RuntimeException implements ValidationException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf(
            'Duplicate validation rule detected for key "%s"',
            $key
        ), 500);
    }
}
