# Results

Results in phly-rule-validation implement `Phly\RuleValidation\ValidationResult`:

```php
/**
 * @template T
 */
interface ValidationResult
{
    /** @psalm-return non-empty-string */
    public function key(): string;

    public function isValid(): bool;

    /** @return T */
    public function value(): mixed;

    public function message(): ?string;
}
```

These methods allow consumers to understand:

- What _data item_ was validated
- Whether or not that item _is valid_
  - and _what was wrong_ when invalid, via the _message_
- What value is associated after validation

> The value associated in a result might not be the value provided during validation.
> As an example, a date or time coming from an HTML form will be a string, but if the value is valid, a rule might cast it to a `DateTimeInterface` instance.
> Similarly, integer and float values coming from an HTML form are always strings, but if they are valid, might be cast to the appropriate type.
> The point is that in a _result_, if the value is valid, we now have the _valid value_.

## The Result class

This library provides a generic `ValidationResult` implementation via `Phly\RuleValidationResult\Result`.
The class provides implementations of each of the interface methods, but also provides a number of _named constructors_:

```php
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
    public static function forValidValue(string $key, mixed $value): self;

    /**
     * @psalm-param non-empty-string $key
     */
    public static function forInvalidValue(string $key, mixed $value, string $message): self;

    /**
     * @psalm-param non-empty-string $key
     * @return self<null>
     */
    public static function forMissingValue(string $key, string $message = self::MISSING_MESSAGE): self;
}
```

These methods are the only way to create a `Result` instance, and allow you to quickly create instances for valid, invalid, and missing result states.

> #### What is a "missing" state?
> 
> Call `Result::forMissingValue()` when a value is required, but a key matching that value was not found in the data set under validation.

As examples:

```php
// A valid value
$result = Result::forValidValue('title', $title);

// An invalid value
$result = Result::forInvalidValue('title', $title, 'Title must be a non-empty string and at least 3 characters long');

// Value is missing
$result = Result::forMissingValue('title', 'Please provide a title');
```

-----

- [Back to Table of Contents](../README.md)
