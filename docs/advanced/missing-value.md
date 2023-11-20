# Handling Missing Values

The standard way to generate a `ValidationResult` representing a missing value is to call `Phly\RuleValidation\Result\Result::forMissingValue()`.
That factory method produces a `Result` for the associated key, and uses the `Result::MISSING_MESSAGE` constant if no `$message` argument is provided.
The `Result` it returns wraps a `null` value.

This might not be what you want.
For instance, you might want to provide a more specific error message.
Or you may want to ensure that the value associated is a valid empty value for the context (e.g., an empty string for a text input, or a `DateTimeInterface` instance for a date selector).

## Customizing the message

The first thing you can do is to customize the associated message.
As an example, in your `Rule` implementation, you could define the `missing()` method to do so:

```php
public function missing(): Result
{
    return Result::forMissingValue(
        $this->key,
        'The "title" is a required value, but was not provided'
    );
}
```

## Customizing the value

To customize the value, you can return a result representing an invalid value:

```php
/** @return Result<DateTimeInterface> */
public function missing(): Result
{
    return Result::forInvalidValue(
        $this->key,
        new DateTimeImmutable(), // default value!
        'The creation date is a required value, but was not provided; select a valid date'
    );
}
```

The above returns a result that wraps a `DateTimeInterface`, and provides a custom message.

## Return a custom ValidationResult

The third option is to return a custom `ValidationResult` implementation.
Let's assume you have defined the class `DateTimeResult` such that it wraps a `DateTimeInterface`, and defines a `forMissingValue()` method that wraps a valid `DateTimeInterface` instance.
You could then write the `missing()` implementation as follows:

```php
public function missing(): DateTimeResult
{
    return DateTimeResult::forMissingValue(
        $this->key,
        'The creation date is a required value, but was not provided; select a valid date'
    );
}
```

Note that the return type hint has changed to represent the more specific type we are returning as well.

-----

- [Back to Table of Contents](../README.md)
