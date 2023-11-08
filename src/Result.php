<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

final class Result
{
    private function __construct(
        public readonly bool $isValid,
        public readonly mixed $value,
        public readonly ?string $message = null,
    ) {
    }

    public static function forValidValue(mixed $value): self
    {
        return new self(isValid: true, value: $value);
    }

    public static function forInvalidValue(mixed $value, string $message): self
    {
        return new self(isValid: false, value: $value, message: $message);
    }

    public static function forMissingValue(string $message): self
    {
        return new self(isValid: false, value: null, message: $message);
    }
}
