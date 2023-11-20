# Providing Type Information

One goal of this library is to make it possible to provide type information for static analysis and for IDEs.
Almost every interface and implementation in the library provides type templates to make this possible, and the few extension points exist for the purpose of allowing users to provide type information.

## Results

Recall that the [`ValidationResult` class](../reference/result.md) declares the following as part of its definition:

```php
namespace Phly\RuleValidation;

/**
 * @template T
 */
interface ValidationResult
{
    /** @return T */
    public function value(): mixed;
}
```

This means that you can indicate that a result wraps a value of a specific type:

```php
/** @var ValidationResult<DateTimeInterface> $date */
echo $date->value()->format('Y-m-d');
```

The `Result` type declares this same template, so you can also write the above as follows if you are using the shipped result type:

```php
/** @var Result<DateTimeInterface> $date */
echo $date->value()->format('Y-m-d');
```

When you write your own implementations, implement the template:

```php
/** @template-implements ValidationResult<int> */
class IntegerResult implements ValidationResult
{
    // ...
}
```

What if you want to allow multiple types?

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

You can then hint on these types:

```php
/** @var IntegerResult $count */
$count = $form->count;

/** @var NumericResult<float> $currency */
$currency = $form->currency;
```

## Result Sets

As [noted in the ResultSet reference](../reference/result-set.md#why-is-everything-marked-final), all the methods are marked final; why extend it then?

To provide typehints for the composed results!

```php
/**
 * @property-read TextResult $title
 * @property-read NumericResult<float> $amount
 */
class TransactionFormResult extends ResultSet
{
}
```

This will allow you to annotate instances:

```php
/** @var TransactionFormResult $result */
$result = $ruleSet->validate($data);

// Static analysis and IDEs will then understand that
// $amount is a float:
$amount = $result->amount->value();
```

It also gives you a value you can use with rule sets.

## Rules

When defining a rule, ensure your `validate()`, `default()`, and `missing()` methods declare what they return.

You could define the more specific `ValidationResult` implementation they return:

```php
public function validate(mixed $value, array $context): IntegerResult;
```

Or you could provide a return annotation that declares the `ValidationResult` type:

```php
/**
 * @return Result<int>
 */
public function validate(mixed $value, array $context): Result
```

## Rule Sets

The `RuleSet` class defines `@template T of ResultSet`.
This gives you a couple possibilities.

First, when using the `RuleSetOptions`, you can provide the result set class to use, and you can then hint to static analysis and IDEs what you are using:

```php
$options = new RuleSetOptions();
$options->setResultSetClass(TransactionFormResult::class);
$options->addRule(/* ... */);

/** @var RuleSet<TransactionFormResult> $ruleSet */
$ruleSet = new RuleSet($options);
```

Alternately, you could extend the class:

```php
/**
 * @template-extends RuleSet<TransactionFormResult>
 */
class TransactionForm extends RuleSet
{
    public function __construct($options)
    {
        $actualOptions = new ResultSetOptions();
        $actualOptions->setResultSetClass(TransactionFormResult::class);
        foreach($options->rules() as $rule) {
            $actualOptions->addRule($rule);
        }

        parent::__construct($actualOptions);
    }
}
```

If you are creating an anonymous class extending `RuleSet`, use the `@template-extends` annotation:

```php
/** @var RuleSet<TransactionForm> $ruleSet */
$ruleSet = new /** @template-extends RuleSet<TransactionFormResult> */ class($options) extends RuleSet {
    // ...
};
```

-----

- [Back to Table of Contents](../README.md)
