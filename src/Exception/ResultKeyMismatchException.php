<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Exception;

use Phly\RuleValidation\Result;
use Phly\RuleValidation\ResultSet;
use RuntimeException;

use function sprintf;

class ResultKeyMismatchException extends RuntimeException implements ValidationException
{
    public static function forKeys(string $resultKey, string $offsetKey): self
    {
        return new self(sprintf(
            'Attempted to assign %s with key "%s" to %s offset "%s"; offset key must match result key',
            Result::class,
            $resultKey,
            ResultSet::class,
            $offsetKey,
        ), 500);
    }
}
