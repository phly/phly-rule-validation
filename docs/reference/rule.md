# Validation Rules

For each datum you wish to validate, you will need to create a `Phly\RuleValidation\Rule` implementation.
That interface is as follows:

```php
namespace Phly\RuleValidation;

interface Rule
{
    public function required(): bool;

    /**
     * Return the key the Rule applies to
     *
     * @return non-empty-string
     */
    public function key(): string;

    /**
     * Validate the value
     *
     * @param array<non-empty-string, mixed> $context
     */
    public function validate(mixed $value, array $context): ValidationResult;

    /** ValidationResult to use when not required and no value provided */
    public function default(): ValidationResult;

    /** ValidationResult to use when required but no value provided */
    public function missing(): ValidationResult;
}
```

The `key()` method returns what key the rule applies to, and is used by rule sets to match an input key with a rule.

The `required()` method allows consumers to know if a rule is required; if it is, then validation will fail if no value is provided for it during validation.
In that particular case, a rule set will call the `missing()` method to produce a `ValidationResult` reporting the missing value.

The `default()` method is called in two cases:

- If no value was provided for the associated key, and `required()` returns `false`, then `default()` will be called to return a validation result representing a valid value with a default value composed.
- When producing an initial "valid" result set (see the [RuleSet section](rule-set.md) for more details), this will return a valid validation result with a default value composed.

## Validation

The `validate()` method expects two arguments:

- The value to validate
- An array of data representing the validation _context_

The validation context is the full data set under validation.
Consumers can use this when validation depends on other values in the data set; as some examples:

- A "password confirmation" might be valid only if there is a matching "password" value.
- An "age verification" rule might be required only if another option was selected.

One thing to note: the context is the raw data, and there is no data present regarding its validity.

## Example

The following is an example "boolean" rule, and shows how you might define a custom `Rule` implementation.
This example could also be created as an anonymous class, if you did not want to write a re-usable `Rule` class implementation.

```php
use Phly\RuleValidation\Result\Result;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\ValidationResult;

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

    public function default(): ValidationResult
    {
        return Result::forValidValue($this->key, $this->default);
    }

    public function missing(): ValidationResult
    {
        // In HTML forms, a boolean value is omitted when toggled off
        return Result::forValidValue($this->key, false);
    }
}
```

-----

- [Back to Table of Contents](../README.md)
