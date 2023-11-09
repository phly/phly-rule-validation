# phly/phly-rule-validation

This library provides a barebones validation framework.

## Goals of this library

The explicit goals of this library are:

- Provide an idempotent way to validate individual items and/or data sets.
- Provide an extensible framework for developing validation rules.
- Allow handling optional data, with default values.
- Allow reporting validation error messages.
- Ensure missing required values are reported as validation failures.
- Use as few dependencies as possible.

Non-goals:

- Creating an extensive set of validation rule classes.
- Providing mechanisms for handling nested data sets.
- Providing a configuration-driven mechanism for creating rule sets.

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
use Phly\RuleValidation\Result;
use Phly\RuleValidation\RuleSet;
use Phly\RuleValidation\Rule\CallbackRule;

$rules = new RuleSet();
$rules->add(new CallbackRule('flag', function (mixed $value, array $data): Result {
    if (! is_bool($value)) {
        return Result::forInvalidValue('flag', $value, 'Not a boolean value');
    }
    return Result::forValidValue('flag', $value);
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
$flagResult = $result['flag']; // or $result->getResultForKey('flag')

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
    public const MISSING_MESSAGE = 'Missing required value';

    public static function forValidValue(string $key, mixed $value): self;

    public static function forInvalidValue(string $key, mixed $value, string $message): self;

    public static function forMissingValue(string $key, string $message = self::MISSING_MESSAGE): self;
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
```

### Shipped Rules

#### BooleanRule

The class `Phly\RuleValidation\Rule\BooleanRule` allows testing for boolean values.
If the value is not boolean, validation fails.

```php
$rule = new BooleanRule(key: 'flag', required: true, default: true);
```

#### CallbackRule

The class `Phly\RuleValidation\Rule\CallbackRule` allows providing a callback to execute during validation.
This callback should have the following signature:

```php
function (mixed $value, array $context, string $key): \Phly\RuleValidation\Result
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
public function __construct(
    string $key,
    callable $callback,
    bool $required = true,
    mixed $default = null,
)
```

Using named arguments, you can create instances with different behavior:

```php
$callback = function (mixed $value, array $context, string $key): Result { /* ... */ };

$rule1 = new CallbackRule(key: 'first', callback: $callback, default: 'string');
$rule2 = new CallbackRule(key: 'second', callback: $callback, required: false);
```

## RuleSet behavior

A `Phly\RuleValidation\RuleSet` is an iterable collection of `Phly\RuleValidation\Rule` instances.

Internally, it validates each item provided to the collection to (a) ensure they are a `Phly\RuleValidation\Rule` instance, and (b) ensure that no rules with the same `Rule::key()` value are added to the collection.

> If a rule is added to the set where `Rule::key()` matches the key of another rule already present, the `RuleSet` will raise a `Phly\RuleValidation\Exception\DuplicateRuleKeyException`.

Most commonly, you will add rules in the following ways:

- Via the constructor, one rule per argument:

  ```php
  $ruleSet = new RuleSet(/* ... Rule instances ... */);
  ```

- Via the constructor, as an array of `Rule` instances:

  ```php
  $ruleSet = new RuleSet(...$arrayOfRuleInstances);
  ```

- Using the `add()` method:

  ```php
  $ruleSet = new RuleSet();
  $ruleSet->add($rule);
  ```

It defines the following methods, which are all marked final:

```php
namespace Phly\RuleValidation;

class RuleSet implements IteratorAggregate
{
    public function getIterator(): Traversable;

    public function add(Rule $rule): void;

    /** Returns the Rule where `Rule::key()` matches the `$key`, and null if none found */
    public function getRuleForKey(string $key): ?Rule;

    public function validate(array $data): ResultSet;
}
```

Additionally, it defines one method that can be overridden:

```php
public function createMissingValueResultForKey(string $key): Result
```

This method is covered below under the heading `Customizing "missing value" messages`.

### Validation behavior

When validating a set of data, `RuleSet` does the following:

- Loops through each `Rule` in the `RuleSet`
- If the `Rule::key()` exists in the `$data`, it passes that value _and_ the data to the rule's `validate()` method to get a validation result.
- If the `Rule::key()` **does not exist** in the `$data`, and the rule is **required**, it generates a `Result` for a missing value.
- If the `Rule::key()` **does not exist** in the `$data`, but the rule is **not required**, it generates a valid `Result` using the rule's `Rule::default()` value.

Results are aggregated in a `Phly\RuleValidation\ResultSet` instance, where the keys correspond to the associated `Rule::key()`.
The `ResultSet` instance returned is _frozen_, and no additional `Result` instances may be `add()`ed to it; attempts to do so will result in a `Phly\Exception\ResultSetFrozenException`.

### Customizing "missing value" messages

By default, when a `RuleSet` generates a result representing a missing value, it does so by calling `Result::forMissingValue()` without any arguments, which in turn uses the `Result::MISSING_MESSAGE` constant value for the `Result::$message` property.

If you want to customize these messages, you have two options:

- In the code where you want to display a validation error message, you can compare the message to the `Result::MISSING_MESSAGE` constant, and, when a match, display your own message:

  ```php
  if (! $result->isValid) {
      echo $result->message === $result::MISSING_MESSAGE ? 'The value was not provided' : $result->message;
  }
  ```

- Extend the `RuleSet` class, and override the `createMissingValueResultForKey()` method:

  ```php
  use Phly\RuleValidation\Result;
  use Phly\RuleValidation\RuleSet;

  class MyCustomRuleSet extends RuleSet
  {
      private const MISSING_KEY_MAP = [
          'title' => 'Please provide a title',
          // ...
      ];

      public function createMissingValueResultForKey(string $key): Result
      {
          if (array_key_exists($key, self::MISSING_KEY_MAP)) {
              return Result::forMissingValue(self::MISSING_KEY_MAP[$key]);
          }

          return Result::forMissingValue();
      }
  }
  ```

## ResultSet behavior

Validation of a `Phly\RuleValidation\RuleSet` produces a `Phly\Validation\ResultSet`.
Similar to the `RuleSet`, `ResultSet` is an iterable collection of `Phly\Validation\Result` instances.
It defines the following methods:

```php
final class ResultSet implements IteratorAggregate
{
    public function getIterator(): Traversable;

    public function add(Result $result): void;

    public function isValid(): bool;

    /** @return array<string, string> */
    public function getMessages(): array;

    /** @return array<string, mixed> */
    public function getValues(): array;

    /** @throws Phly\RuleValidation\Exception\UnknownResultException */
    public function getResultForKey(string $key): Result;

    /**
     * Freeze the result set
     *
     * Once called, no more results may be added to the result set.
     */
    public function freeze(): void
}
```

You can retrieve individual `Phly\RuleValidation\Result` instances using the `getResultForKey(string $key)` method.

> Internally, `RuleSet` calls `freeze()` on a `ResultSet` before returning it from `RuleSet::validate()`.

Access individual results when generating an HTML form:

```php
<?php $title = $results->getResultForKey('title'); // or $results['title'] ?>
<input type="text" name="title" value="<?= $this->e($title->value) ?>">
<?php if (! $title->isValid): ?>
<p class="form-error"><?= $this->e($title->message) ?></p>
<?php endif ?>
```

> The above example is using the [PlatesPHP templating engine](https://platesphp.com/).

### Result class

The `Phly\RuleValidation\Result` class has the following API:

```php
namespace Phly\RuleValidation;

final readonly class Result
{
    public string $key;
    public bool $isValid;
    public mixed $value;
    public ?string $message = null;

    public static function forValidValue(mixed $value): self;

    public static function forInvalidValue(mixed $value, string $message): self;

    public static function forMissingValue(string $message): self;
}
```

Your `Rule` classes will produce `Result` instances using one of the named constructors (`forValidValue()`, `forInvalidValue()`), and your code can then access the state using the public properties (`$isValid`, `$value`, `$message`).
