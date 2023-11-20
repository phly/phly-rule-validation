# Rule Sets

A _rule set_ is a collection of `Phly\RuleValidation\Rule` instances.
Within phly-rule-validation, a rule set implements `Phly\RuleValidation\RuleSetValidator`:

```php
/** @template T of ResultSet */
interface RuleSetValidator
{
    /**
     * @param array<non-empty-string, mixed> $data
     * @return T
     */
    public function validate(array $data): ResultSet;

    /**
     * @param array<non-empty-string, mixed> $valueMap
     * @return T
     */
    public function createValidResultSet(array $valueMap = []): ResultSet;

    /** @param non-empty-string $key */
    public function getRule(string $key): ?Rule;
}
```

You will note that the only aspect of a `RuleSetValidator` that requires it composes any `Rule` instances is the `getRule()` method, and since it has a nullable return type, rules are completely optional even for implementations.
As such, the pieces of import are the `validate()` and `createValidResultSet()` methods.

The `validate()` method takes the array of data to validate, and returns a `ResultSet`.
This is the primary purpose of a result set.

The `createValidResultSet()` method takes an optional value map of key/value pairs, and uses it to seed a `ResultSet` representing an initial valid state; see the section on [Creating valid result sets](#creating-valid-result-sets) below for more information.

This interface allows defining validation for arbitrary data sets, and is guaranteed to return result sets upon completion of validation.
However, in most cases, you will not want to define your own implementations; for this, this library ships a standard implementation, `Phly\Rule\RuleSet\RuleSet`.

## The RuleSet class

A `Phly\RuleValidation\RuleSet` is a collection of `Phly\RuleValidation\Rule` instances.

Internally, it validates each item provided to the collection to (a) ensure they are a `Phly\RuleValidation\Rule` instance, and (b) ensure that no rules with the same `Rule::key()` value are added to the collection.

> If a rule is added to the set where `Rule::key()` matches the key of another rule already present, the `RuleSet` will raise a `Phly\RuleValidation\Exception\DuplicateRuleKeyException`.

Most commonly, you will create an instance with the rules you wish to use via the named constructor `createWithRules()`, one rule per argument:

```php
$ruleSet = RuleSet::createWithRules(/* ... Rule instances ... */);
```

Optionally, you can create an instance of `Phly\Rule\RuleSet\RuleSetOptions`, and `add()` rules to it:

```php
$options = new RuleSetOptions();
$options->addRule($titleRule);
$options->addRule($descriptionRule);
$options->addRule($contentRule);
```

You will then pass this to the constructor:

```php
$ruleSet = new RuleSet($options);
```

`RuleSet` implements `RuleSetValidator`, and defines the following additional methods:

```php
/**
 * @template T of ResultSet
 * @template-implements RuleSetValidator<T>
 */
class RuleSet implements RuleSetValidator
{
    // Extension points

    /** @return self<ResultSet> */
    public static function createWithRules(Rule ...$rules): self;

    public function __construct(Options $options);

    /**
     * @param array<non-empty-string, mixed> $valueMap
     * @return T
     */
    public function createValidResultSet(array $valueMap = []): ResultSet;

    // Other non-interface methods:

    /** @param non-empty-string $key */
    final public function __isset($key): bool;
    /** @param non-empty-string $key */
    final public function __get($key): ?Rule;
}
```

All other methods are marked final.

Note that this implementation defines the methods `__isset()` and `__get()`, which allow property access to the composed rules, using their associated keys.

### Options

As noted in the previous section, the `RuleSet` constructor expects a `Phly\RuleValidation\RuleSet\Options` instance.
`Options` is an interface with the following definition:

```php
interface Options
{
    /** @return class-string<ResultSet> */
    public function resultSetClass(): string;

    /** @return Rule[] */
    public function rules(): array;
}
```

The `Phly\RuleValidation\RuleSet\RuleSetOptions` implementation defines two additional methods:

```php
class RuleSetOptions implements Options
{
    /** @param class-string<ResultSet> $class */
    public function setResultSetClass(string $class): void;
    public function addRule(Rule $rule): void;
}
```

If you provide a result set class via the options, `RuleSet` will create an instance of that class in each of its `validate()` and `createValidResultSet()` methods, passing it the calculated results as individual arguments to the constructor, which allows you to have type-safety for the composed results of that instance.

### Validation behavior

When validating a set of data, `RuleSet` does the following:

- Loops through each `Rule` in the `RuleSet`
- If the `Rule::key()` exists in the `$data`, it passes that value _and_ the data to the rule's `validate()` method to get a validation result.
- If the `Rule::key()` **does not exist** in the `$data`, and the rule is **required**, it calls on the rule's `missing()` method to generate a validation result.
- If the `Rule::key()` **does not exist** in the `$data`, but the rule is **not required**, it generates a valid `ValidationResult` using the rule's `Rule::default()` method.

Results are aggregated in a `Phly\RuleValidation\ResultSet` instance, where the keys correspond to the associated `Rule::key()`.

### Creating valid result sets

The purpose of the `RuleSetValidator::createValidResultSet()` method is to allow generating an initial state for use with HTML forms:

```php
<?php
// In a controller or request handler:
$html = $template->render('app::form', ['form' => $rules->createValidResultSet()]);
?>

<!-- In the template -->
<?php if (! $form->isValid()): ?>
<!-- render an error message -->
<?php endif ?>

<input name="title" type="text" value="<?= $this->e($form->title->value()) ?>">
<?php if (! $form->title->isValid()): ?>
<p class="text-warning"><?= $form->title->message() ?></p>
<?php endif ?>
```

Since a result set created with `createValidResultSet()` _should_ be considered valid, the above would skip the error messages on initial render, but rendering of the input would work successfully, as we would have a `ValidationResult` present in the result set, with a value. 

Implementations would then have to choose how to manage the `getRule()` method.
Assuming there is a rule defined per input key, this method would return the associated rule.

The `createValidResultSet()` method takes an optional associative array with values to use to seed the `Result` values associated with the provided key; it uses `Result::forValidValue()` internally to generate the instance.
Any keys in that value map that do not have an associated rule are ignored.
If the value map does not contain a key for a composed `Rule`, then `RuleSet` will call the `default()` method for the `Rule` instance to obtain a validation result.

The values in the `$valueMap` always take precedence over default values.

-----

- [Back to Table of Contents](../README.md)
