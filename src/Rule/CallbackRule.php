<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Rule;

use Phly\RuleValidation\Result;
use Phly\RuleValidation\Rule;

final class CallbackRule implements Rule
{
    /** @var callable(mixed, array, non-empty-string): Result */
    private $callback;

    /** @param callable(mixed, array, non-empty-string): Result $callback */
    public function __construct(
        /** @var non-empty-string */
        private string $key,
        callable $callback,
        private bool $required = true,
        private mixed $default = null,
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

    /** Validate the value */
    public function validate(mixed $value, array $context): Result
    {
        return ($this->callback)($value, $context, $this->key);
    }

    public function default(): mixed
    {
        return $this->default;
    }
}
