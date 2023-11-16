<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

/**
 * @template T
 * @template-implements ValidationResult<T>
 */
class Result implements ValidationResult
{
    public const MISSING_MESSAGE = 'Missing required value';

    /**
     * @template V
     * @psalm-param non-empty-string $key
     * @psalm-param V $value
     * @return self<V>
     */
    public static function forValidValue(string $key, mixed $value): self
    {
        return new self(key: $key, isValid: true, value: $value);
    }

    /**
     * @psalm-param non-empty-string $key
     */
    public static function forInvalidValue(string $key, mixed $value, string $message): self
    {
        return new self(key: $key, isValid: false, value: $value, message: $message);
    }

    /**
     * @psalm-param non-empty-string $key
     * @return self<null>
     */
    public static function forMissingValue(string $key, string $message = self::MISSING_MESSAGE): self
    {
        return new self(key: $key, isValid: false, value: null, message: $message);
    }

    /** @return non-empty-string */
    public function key(): string
    {
        return $this->key;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    /** @return T */
    public function value(): mixed
    {
        return $this->value;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    private function __construct(
        /** @var non-empty-string */
        private string $key,
        private bool $isValid,
        /** @var T */
        private mixed $value,
        private ?string $message = null,
    ) {
    }
}
