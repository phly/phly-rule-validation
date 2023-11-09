# phly/phly-rule-validation

This library provides a barebones validation framework.

## Installation

```bash
composer require phly/phly-rule-validation
```

## Usage

To define a set of validation rules, you will define a _rule set_ and add _rules_ to the rule set.
You then _validate_ data against the rule set, which returns a _result set_.
Each _result_ in the result set contains the value provided, whether or not it is valid, and, if invalid, an error message describing why.

In practice:

```php
use Phly\RuleValidation\CallbackRule;
use Phly\RuleValidation\Result;
use Phly\RuleValidation\RuleSet;

$rules = new RuleSet();
$rules->add(new CallbackRule('flag', function (mixed $value, array $data): Result {
    if (! is_bool($value)) {
        return Result::forInvalidValue($value, 'Not a boolean value');
    }
    return Result::forValidValue($value);
}, default: false));
$rules->add(new MyCustomRule());
// ...

$result = $rules->validate($someFormData);

if ($result->isValid()) {
    $values = $result->getValues();
    // do something with values
} else {
    $messages = $result->getMessages();
    // do something with error messages
}

// Get a result for a single key:
$flagResult = $result['flag'];

// Get the value from a single result
$flag = $flagResult->value;

// Get the validation status from a single result
if ($flagResult->isValid) {
    // ...
}

// Get an error message for a single result
if (! $flagResult->isValid) {
    echo $flagResult->message;
}
```

## Defining Rules

For each datum you wish to validate, you will need to create a `Phly\RuleValidation\Rule` implementation.
That interface is as follows:

```php
namespace Phly\RuleValidation;

interface Rule
{
    public function required(): bool;

    /** Return the key the Rule applies to */
    public function key(): string;

    /** Validate the value */
    public function validate(mixed $value, array $context): Result;

    /** Default value to use when not required and no value provided */
    public function default(): mixed;
}
```

During the operation of `validate()`, each `Rule` must create and return a `Phly\RuleValidation\Result`.
This can be done with one of the following named constructors:

```php
namespace Phly\RuleValidation;

final class Result
{
    public static function forValidValue(mixed $value): self;

    public static function forInvalidValue(mixed $value, string $message): self;

    public static function forMissingValue(string $message): self;
}
```

As an example, if you wanted to create a rule to validate that a value is a boolean:

```php
use Phly\RuleValidation\Result;
use Phly\RuleValidation\Rule;

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
            return Result::forInvalidValue($value, sprintf(
                'Expected boolean value; received %s',
                get_debug_type($value),
            ));
        }

        return Result::forValidValue($value);
    }

    public function default(): mixed
    {
        return $this->default;
    }
}
```

### Shipped Rules

#### CallbackRule

The class `Phly\RuleValidation\CallbackRule` allows providing a callback to execute during validation.
This callback should have the following signature:

```php
function (mixed $value, array $context): \Phly\RuleValidation\Result
```

As an example of valid callback:

```php
function (mixed $value, array $data): Result {
    if (! is_bool($value)) {
        return Result::forInvalidValue($value, 'Not a boolean value');
    }
    return Result::forValidValue($value);
}
```

The constructor of `CallbackRule` has the following signature:

```php
public function __construct(
    string $key
    callable $callback,
    bool $required = true,
    mixed $default = null,
)
```

Using named arguments, you can create instances with different behavior:

```
$callback = function (mixed $value, array $context): Result { /* ... */ };

$rule1 = new CallbackRule('first', $callback, default: 'string');
$rule2 = new CallbackRule('second', $callback, required: false);
```

## RuleSet behavior

A `Phly\RuleValidation\RuleSet` is an extension of [Ramsey\\Collection\\Collection](https://github.com/ramsey/collection), and typed against `Phly\RuleValidation\Rule`.

Internally, it validates each items provided to the collection to (a) ensure they are a `Phly\RuleValidation\Rule` instance, and (b) ensure that no rules with the same `Rule::key()` value are added to the collection.

> If a rule is added to the set where `Rule::key()` matches the key of another rule already present, the `RuleSet` will raise a `Phly\RuleValidation\Exception\DuplicateKeyException`.

Most commonly, you will add rules in the following ways:

- Via the constructor:

  ```php
  $ruleSet = new RuleSet([/* ... Rule instances ... */]);
  ```

- Using the `add()` method:

  ```php
  $ruleSet = new RuleSet();
  $ruleSet->add($rule);
  ```

- Using array append notation:

  ```php
  $ruleSet = new RuleSet();
  $ruleSet[] = $rule;
  ```

It also adds two methods:

```php
/** Returns the Rule where `Rule::key()` matches the `$key`, and null if none found */
public function getRuleForKey(string $key): ?Rule;

public function validate(array $data): ResultSet;
```

### Validation behavior

When validating a set of data, `RuleSet` does the following:

- Loops through each `Rule` in the `RuleSet`
- If the `Rule::key()` exists in the `$data`, it passes that value _and_ the data to the rule's `validate()` method to get a validation result.
- If the `Rule::key()` **does not exist** in the `$data`, and the rule is **required**, it generates a `Result` for a missing value.
- If the `Rule::key()` **does not exist** in the `$data`, but the rule is **not required**, it generates a valid `Result` using the rule's `Rule::default()` value.

Results are aggregated in a `Phly\RuleValidation\ResultSet` instance, where the keys correspond to the associated `Rule::key()`.

## ResultSet behavior

Validation of a `Phly\RuleValidation\RuleSet` produces a `Phly\Validation\ResultSet`.
Like the `RuleSet`, `ResultSet` is a [Ramsey\\Collection\\Collection](https://github.com/ramsey/collection) extension.
It defines three additional methods:

```php
final class ResultSet extends \Ramsey\Collection\AbstractCollection
{
    public function isValid(): bool;

    /** @return array<string, null|string> */
    public function getMessage(): array;

    /** @return array<string, mixed> */
    public function getValues(): array;
}
```

Unlike `RuleSet`, `ResultSet` maps the rule key to each `Phly\RuleValidation\Result` in the collection, allowing you to retrieve results for individual keys:

```php
$result = $resultSet[$key];
```

The above is useful when generating an HTML form:

```php
<input type="text" name="title" value="<?= $this->e($results['title']->value) ?>">
<?php if (! $results['title']->isValid): ?>
<p class="form-error"><?= $this->e($results['title']->message) ?></p>
<?php endif ?>
```

> The above example is using the [PlatesPHP templating engine](https://platesphp.com/).

### Result class

The `Phly\RuleValidation\Result` class has the following API:

```php
namespace Phly\RuleValidation;

final readonly class Result
{
    public bool $isValid,
    public mixed $value,
    public ?string $message = null,

    public static function forValidValue(mixed $value): self;

    public static function forInvalidValue(mixed $value, string $message): self;

    public static function forMissingValue(string $message): self;
}
```

Your `Rule` classes will produce `Result` instances using one of the named constructors (`forValidValue()`, `forInvalidValue()`), and your code can then access the state using the public properties (`$isValid`, `$value`, `$message`).
