<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Rule;

use Phly\RuleValidation\Result\Result;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\ValidationResult;

use function get_debug_type;
use function is_bool;
use function sprintf;

class BooleanRule implements Rule
{
    public function __construct(
        /** @var non-empty-string */
        private string $key,
        private bool $required = true,
        /** @var null|ValidationResult When not provided, a Result instance with value false is used */
        private ?ValidationResult $default = null,
        /** @var null|ValidationResult When not provided, a Result instance is used */
        private ?ValidationResult $missingResult = null,
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

    /**
     * Validate the value
     *
     * @return ValidationResult<bool>
     */
    public function validate(mixed $value, array $context): ValidationResult
    {
        if (! is_bool($value)) {
            return Result::forInvalidValue($this->key, $value, sprintf(
                'Expected boolean value; received %s',
                get_debug_type($value),
            ));
        }

        return Result::forValidValue($this->key, $value);
    }

    public function default(): ValidationResult
    {
        return $this->default ?: Result::forValidValue($this->key, false);
    }

    public function missing(): ValidationResult
    {
        return $this->missingResult ?: Result::forMissingValue($this->key);
    }
}
