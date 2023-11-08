<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

final class CallbackRule implements Rule
{
    /** @var pure-callable(mixed, array): Result */
    private $callback;

    /** @param pure-callable(mixed, array): Result $callback */
    public function __construct(
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

    public function for(): string
    {
        return $this->key;
    }

    /** Validate the value */
    public function validate(mixed $value, array $context): Result
    {
        return ($this->callback)($value, $context);
    }

    public function default(): mixed
    {
        return $this->default;
    }
}
