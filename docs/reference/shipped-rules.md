# Shipped Rules

Per the goals of this library, very few rules are shipped as part of the package.
This page details the few rules provided.

#### BooleanRule

The class `Phly\RuleValidation\Rule\BooleanRule` allows testing for boolean values.
If the value is not boolean, validation fails.

```php
$rule = new BooleanRule(key: 'flag', required: true, default: Result::forValidValue('flag', true));
```

Its full constructor:

```php
/**
 * @param non-empty-string $key
 * @param null|ValidationResult $default When not provided, a Result instance with value false is used
 * @param null|ValidationResult $missingResult When not provided, a Result instance is used
 */
public function __construct(
    string $key,
    bool $required = true,
    ?ValidationResult $default = null,
    ?ValidationResult $missingResult = null,
) {
}
```

#### CallbackRule

The class `Phly\RuleValidation\Rule\CallbackRule` allows providing a callback to execute during validation, and provides a ready-to-use, generic validation solution.

This callback should have the following signature:

```php
/**
 * @param array<non-empty-string, mixed> $context
 * @param non-empty-string $key
 */
function (mixed $value, array $context, string $key): \Phly\RuleValidation\ValidationResult
```

As an example of valid callback:

```php
function (mixed $value, array $data, string $key): Result {
    if (! is_bool($value)) {
        return Result::forInvalidValue($key, $value, 'Not a boolean value');
    }
    return Result::forValidValue($key, $value);
}
```

The constructor of `CallbackRule` has the following signature:

```php
/**
 * @param non-empty-string $key
 * @param callable(mixed, array<non-empty-string, mixed>, non-empty-string): ValidationResult $callback
 */
public function __construct(
    string $key,
    callable $callback,
    bool $required = true,
    ?ValidationResult $default = null,
    ?ValidationResult $missing = null,
)
```

Using named arguments, you can create instances with different behavior:

```php
$callback = function (mixed $value, array $context, string $key): Result { /* ... */ };

$rule1 = new CallbackRule(key: 'first', callback: $callback, default: Result::forValidValue('first', 'string'));
$rule2 = new CallbackRule(key: 'second', callback: $callback, required: false);
```

-----

- [Back to Table of Contents](../README.md)
