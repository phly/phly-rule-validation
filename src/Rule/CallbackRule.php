<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Rule;

use Phly\RuleValidation\Result\Result;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\ValidationResult;

final class CallbackRule implements Rule
{
    /** @var callable(mixed, array<non-empty-string, mixed>, non-empty-string): ValidationResult */
    private $callback;

    /** @param callable(mixed, array<non-empty-string, mixed>, non-empty-string): ValidationResult $callback */
    public function __construct(
        /** @var non-empty-string */
        private string $key,
        callable $callback,
        private bool $required = true,
        private ?ValidationResult $default = null,
        private ?ValidationResult $missing = null,
    ) {
        $this->callback = $callback;
    }

    public function required(): bool
    {
        return $this->required;
    }

    public function key(): string
    {
        return $this->key;
    }

    /**
     * Validate the value
     *
     * @param array<non-empty-string, mixed> $context
     */
    public function validate(mixed $value, array $context): ValidationResult
    {
        return ($this->callback)($value, $context, $this->key);
    }

    public function default(): ValidationResult
    {
        return $this->default ?: Result::forValidValue($this->key, null);
    }

    public function missing(): ValidationResult
    {
        return $this->missing ?: Result::forMissingValue($this->key);
    }
}
