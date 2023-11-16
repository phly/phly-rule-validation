# phly/phly-rule-validation

[![Build Status](https://github.com/phly/phly-rule-validation/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/phly/phly-rule-validation/actions/workflows/continuous-integration.yml)

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
- Providing extensive mechanisms for validating and returning nested data sets.
- Providing a configuration-driven mechanism for creating rule sets.
- Providing HTML form input representations or all metadata required to create HTML form input representations.

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
$flagResult = $result->flag; // or $result->getResultForKey('flag')

// Get the value from a single result
$flag = $flagResult->value();

// Get the validation status from a single result
if ($flagResult->isValid()) {
    // ...
}

// Get an error message for a single result
if (! $flagResult->isValid()) {
    echo $flagResult->message();
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
    public function validate(mixed $value, array $context): ValidationResult;

    /** Default value to use when not required and no value provided */
    public function default(): mixed;
}
```

During the operation of `validate()`, each `Rule` must create and return a `Phly\RuleValidation\Result`.
This can be done with one of the following named constructors:

```php
namespace Phly\RuleValidation;

class Result
{
    public const MISSING_MESSAGE = 'Missing required value';

    final public static function forValidValue(string $key, mixed $value): self;

    final public static function forInvalidValue(string $key, mixed $value, string $message): self;

    final public static function forMissingValue(string $key, string $message = self::MISSING_MESSAGE): self;
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

use Traversable;

class RuleSet implements IteratorAggregate
{
    public static function createWithResultSetClass(string $resultSetClass, Rule ...$rules): self;

    public function getIterator(): Traversable;

    public function add(Rule $rule): void;

    /** Returns the Rule where `Rule::key()` matches the `$key`, and null if none found */
    public function getRuleForKey(string $key): ?Rule;

    public function validate(array $data): ResultSet;

    /** Use this method to create an initial result set for a form */
    public function createValidResultSet(array $valueMap = []): ResultSet;
}
```

Additionally, it defines one method that can be overridden:

```php
public function createMissingValueResultForKey(string $key): ValidationResult
```

This method is covered below under the heading `Customizing "missing value" messages`.

The class also defines one `protected` property for defining an alternate `ResultSet` implementation to create when validating:

```php
/** @var class-string<ResultSet> */
protected string $resultSetClass = ResultSet::class;
```

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

### Creating an initial result set

When preparing an HTML form, it is not uncommon to create one version of the form to handle both the initial form state, as well as to handle cases where form validation fails.
Doing so can result in a lot of logic to determine if values already exist and are valid:

```php
<input name="title" type="text" value="<?= isset($form) ? $this->e($form>title->value()) : '' ?>">
<?php if (isset($form) && ! $form>title->isValid()): ?>
<p class="text-error"><?= $form>title->message() ?></p>
<?php endif ?>
```

This gets even more complicated if the form represents an update of an existing data set:

```php
<input name="title" type="text" value="<?= isset($form) ? $this->e($form->title->value()) : $post->title ?>">
<?php if (isset($form) && ! $form->title->isValid()): ?>
<p class="text-error"><?= $form>title->message() ?></p>
<?php endif ?>
```

The above poses a risk in the scenario that `$post` has not been provided to the template.

To make creating these forms easier, `RuleSet` provides the method `createValidResultSet(array $valueMap = []): ResultSet`.
The method returns a `ResultSet` where `isValid()` returns `true`, and each composed `Result` has a `true` `$isValid` property.

This method takes an optional associative array with values to use to seed the `Result` values associated with the provided key.
Any keys in that value map that do not have an associated rule are ignored.
If the value map does not contain a key for a composed `Rule`, then the following will happen:

- If the value is _required_ but has no associated default value, a `Phly\RuleValidation\Exception\RequiredRuleWithNoDefaultValueException` is raised.
- If the value is optional, and has no associated default value, `null` is used.
- If a default value exists, that value is used.

The values in the `$valueMap` always take precedence over default values.

This approach means that you can always pass a populated `ResultSet` to the template, reducing the boilerplate for checking for forms and their values.

As an example:

```php
$form = $ruleSet->createValidResultSet(['title' => $post->title]);

// In an HTML form:
<input name="title" type="text" value="<?= $this->e($form>title->value()) ?>">
<?php if (! $form>title->isValid()): ?>
<p class="text-error"><?= $form>title->message() ?></p>
<?php endif ?>
```

### Using a custom `ResultSet`

By default, `RuleSet::validate()` will return an instance of `ResultSet` (see next section).
If you wish to provide an alternate `ResultSet`, you can use the `RuleSet::createWithResultSetClass()` constructor:

```php
/** @param class-string<ResultSet> $resultSetClass */
public static function createWithResultSetClass(string $resultSetClass, Rule ...$rules): self
```

> When using this method, we recommend adding an annotation for your rule set variable to denote the result class used:
>
> ```php
> /** @var RuleSet<MyCustomResultSet> $ruleSet */
> $ruleSet = RuleSet::createWithResultSetClass(MyCustomResultSet::class);
> ```

## ResultSet behavior

Validation of a `Phly\RuleValidation\RuleSet` produces a `Phly\Validation\ResultSet`.
Similar to the `RuleSet`, `ResultSet` is an iterable collection of `Phly\Validation\ValidationResult` instances.
It defines the following methods:

```php
class ResultSet implements IteratorAggregate
{
    /** Returns true if a ValidationResult is mapped to the given $key */
    final public function __isset(string $key): bool;

    /** Returns the ValidationResult associated with $key, allowing property access to individual results */
    final public function __get(string $key): ?ValidationResult;

    final public function getIterator(): Traversable;

    final public function add(ValidationResult $result): void;

    final public function isValid(): bool;

    /** @return array<string, string> */
    final public function getMessages(): array;

    /** @return array<string, mixed> */
    final public function getValues(): array;

    /** @throws Phly\RuleValidation\Exception\UnknownResultException */
    final public function getResultForKey(string $key): ValidationResult;

    /**
     * Freeze the result set
     *
     * Once called, no more results may be added to the result set.
     */
    final public function freeze(): void
}
```

> Internally, `RuleSet` calls `freeze()` on a `ResultSet` before returning it from `RuleSet::validate()`.

You can retrieve individual `Phly\RuleValidation\ValidationResult` instances using the `getResultForKey(string $key)` method, or via property access, using the key:

```php
$result = $results->getResultForKey('flag');
$result = $results->flag;
```

Access individual results when generating an HTML form:

```php
<?php $title = $results->title; // or $results->getResultForKey('title') ?>
<input type="text" name="title" value="<?= $this->e($title->value()) ?>">
<?php if (! $title->isValid()): ?>
<p class="form-error"><?= $this->e($title->message()) ?></p>
<?php endif ?>
```

> The above example is using the [PlatesPHP templating engine](https://platesphp.com/).

### ValidationResult interface

The `Phly\Validation\ValidationResult` interface is defined as follows:

```php
namespace Phly\RuleValidation;

/** @template T */
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

### Result class

The `Phly\RuleValidation\Result` class implements `ValidationResult`, and allows for arbitrary values.
In addition to the methods from the interface, it has the following API:

```php
namespace Phly\RuleValidation;

/**
 * @template T
 * @template-implements ValidationResult<T>
 */
class Result implements ValidationResult
{
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

    /** @return self<null> */
    public static function forMissingValue(string $key, string $message): self;
}
```

You can define your `Rule` classes to produce `Result` instances using one of the named constructors (`forValidValue()`, `forInvalidValue()`), and your code can then access the state using the public properties (`$isValid`, `$value`, `$message`).

## Providing types

`Result::value()` provides a `mixed` hint, and a `ResultSet` is defined as composing arbitrary `ValidationResult` instances.
This makes reasoning about what type of value is expected from results, and what results a result set composes, next to impossible.

To provide a bit more safety, this library defines a number of template annotations.

### ValidationResult

As noted in the interface declaration under the [ValidationResult interface section](#validationresult-interface), `ValidationResult` defines a template.
All implementations are expected to implement the template.

This can be done in a couple of ways.

First, by annotating it at the class level:

```php
/** @template-implements ValidationResult<int> */
class IntegerResult implements ValidationResult
{
    // ...
}
```

If you want your result to allow extension itself, or allow userland annotations to define the result type, you can define your own template:

```php
/**
 * @template T of int|float
 * @template-implements ValidationResult<T>
 */
class NumericResult implements ValidationResult
{
    /** @return T */
    public function value(): int|float
    {
    }

    // ...
}
```

Consumers would then denote the type, potentially within a result set:

```php
/**
 * @property-read TextResult $title
 * @property-read NumericResult<float> $amount
 */
class TransactionFormResult extends ResultSet
{
}
```

> See more on this in the section below marked [ResultSet](#resultset).

Or possibly when retrieving it:

```php
/** @var NumericResult<float> $amount */
$amount = $transactionForm->amount;
```

#### Result

An example of the above in practice is the `Result` class, which allows declaring the type of the `$value` it composes:

```php
/** @var Result<int> $count */
$count = $resultSet->count;
```

### ResultSet

The `ResultSet` class can be extended.
When you do so, you may define property annotations for the class to define accessible `Result` instances:

```php
/**
 * @property-read Result<string> $title
 * @property-read Result<string> $description
 * @property-read Result<DateTimeImmutable> $creationDate
 */
class CustomResultSet extends ResultSet
{
}
```

This will allow you to annotate instances:

```php
/** @var CustomResultSet $result */
$result = $ruleSet->validate($data);

// Static analysis and IDEs will then understand that
// $date is a DateTimeImmutable:
$date = $result->creationDate;
```

It also gives you a value you can use with rule sets...

### RuleSet

The `RuleSet` class defines `@template T of ResultSet`.
This gives you a couple possibilities.

For instance, when using the `RuleSet::createWithResultSetClass()` constructor, you could do the following:

```php
/** @var RuleSet<CustomResultSet> $ruleSet */
$ruleSet = RuleSet::createWithResultSetClass(CustomResultSet::class);
```

Alternately, you could extend the class:

```php
/**
 * @template-extends RuleSet<CustomResultSet>
 */
class MyRuleSet extends RuleSet
{
    protected string $resultSetClass = CustomResultSet::class;
}
```

If you are creating an anonymous class extending `RuleSet`, use the `@template-extends` annotation:

```php
$ruleSet = new /** @template-extends RuleSet<CustomResultSet> */ class extends RuleSet {
    // ...
};
```

## Usage Examples

### Nested result sets

While this project does not aim to provide comprehensive support for nested result sets, it _is_ possible to do so, via the `Phly\RuleValidation\NestedResult` class.

Within a `Rule::validate()` implementation, you can choose to return a `NestedResult` instead:

```php
$rule = new /** @template-extends RuleSet<ResultSet> */ class implements Rule {
    /** @var RuleSet{name: Result<string>, email: Result<string>} */
    private RuleSet $rules;

    public function __construct()
    {
        $this->rules = new RuleSet();
        $this->rules->add(/* ... */); // name rule
        $this->rules->add(/* ... */); // email rule
    }

    public function required(): bool
    {
        return true;
    }

    /** Return the key the Rule applies to */
    public function key(): string
    {
        return 'author';
    }

    public function validate(mixed $value, array $context): NestedResult
    {
        if (! is_array($value)) {
            $resultSet = new ResultSet();
            $resultSet->add(Result::forMissingValue('name', 'Name is missing');
            $resultSet->add(Result::forMissingValue('email', 'Email is missing');

            // Note: returning a NestedResult instead of a Result
            return NestedResult::forInvalidValue($this->key(), $resultset, 'author must be an array containing a name and email');
        }

        $result = $this->rules->validate($value)

        if (! $result->isValid()) {
            // Note: returning a NestedResult instead of a Result
            return NestedResult::forInvalidValue($this->key(), $result, 'One or more elements of the author struct were invalid');
        }

        // Note: returning a NestedResult instead of a Result
        return NestedResult::forValidValue($this->key(), $result);
    }

    /** @return RuleSet{name: Result<string>, email: Result<string>} */
    public function default(): mixed
    {
        return $this->rules->createValidResultSet(['name' => '', 'email' => '']);
    }
};
```

Note that the returned `NestedResult` from `validate()` always contains a `ResultSet` as its value.
`NestedResult` provides some convenience via `__isset()` and `__get()` to allow you to get at the `Result` instances of that `ResultSet`:

```php
<?php $author = $form->author; */
<?php if (! $author->isValid()): ?>
<p class="text-warning">Please ensure valid author information is provided</p>
<?php endif ?>
<label for="author-name">Author Name</label>
<input name="author[name]" id="author-name" type="text" value="<?= $this->e($author->name->value()) ?>">
<?php if (! $author->name->isValid()): ?>
<p class="text-warning"><?= $author->name->message() ?></p>
<?php endif ?>
<label for="author-email">Author Name</label>
<input name="author[email]" id="author-email" type="email" value="<?= $this->e($author->email->value()) ?>">
<?php if (! $author->email->isValid()): ?>
<p class="text-warning"><?= $author->email->message() ?></p>
<?php endif ?>
```

Calling `$author->value()` will return the composed `ResultSet`, but calling on one of the composed result keys will give you the associated `ValidationResult` within the `ResultSet` directly.
This is particularly useful when mapping values to a value object or when passing values as arguments:

```php
$author = new Author($author->name->value(), $author->email->value());
```
