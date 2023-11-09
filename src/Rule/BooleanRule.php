<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Rule;

use Phly\RuleValidation\Result;
use Phly\RuleValidation\Rule;

use function get_debug_type;
use function is_bool;
use function sprintf;

class BooleanRule implements Rule
{
    public function __construct(
        private string $key,
        private bool $required = true,
        private bool $default = false,
    ) {
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
        if (! is_bool($value)) {
            return Result::forInvalidValue($this->key, $value, sprintf(
                'Expected boolean value; received %s',
                get_debug_type($value),
            ));
        }

        return Result::forValidValue($this->key, $value);
    }

    public function default(): mixed
    {
        return $this->default;
    }
}
