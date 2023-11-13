<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Exception;

use Phly\RuleValidation\ResultSet;
use RuntimeException;

use function sprintf;

class RequiredRuleWithNoDefaultValueException extends RuntimeException implements ValidationException
{
    /**
     * @psalm-param non-empty-string $key
     * @param class-string<ResultSet> $resultSetClass
     */
    public static function forKey(string $key, string $resultSetClass): self
    {
        return new self(sprintf(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            'Unable to create valid %s instance; key "%s" is required, but has no default value; provide a value via the $valueMap argument',
            $resultSetClass,
            $key,
        ), 500);
    }
}
