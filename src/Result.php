<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

/**
 * @template T
 */
class Result
{
    public const MISSING_MESSAGE = 'Missing required value';

    final protected function __construct(
        public readonly string $key,
        public readonly bool $isValid,
        /** @var T */
        public readonly mixed $value,
        public readonly ?string $message = null,
    ) {
    }

    /**
     * @return Result<T>
     */
    public static function forValidValue(string $key, mixed $value): self
    {
        return new static(key: $key, isValid: true, value: $value);
    }

    /**
     * @return Result<T>
     */
    public static function forInvalidValue(string $key, mixed $value, string $message): self
    {
        return new static(key: $key, isValid: false, value: $value, message: $message);
    }

    /**
     * @return Result<T>
     */
    public static function forMissingValue(string $key, string $message = self::MISSING_MESSAGE): self
    {
        return new static(key: $key, isValid: false, value: null, message: $message);
    }
}
