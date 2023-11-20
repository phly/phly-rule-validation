# Nested Results

While this project does not aim to provide comprehensive support for nested result sets, it does provide some capabilities via the `Phly\RuleValidation\Result\NestedResult` class.

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

    /** @return NestedResult<ResultSet{name: Result<string>, email: Result<string>}> */
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

    /** @return NestedResult<ResultSet{name: Result<string>, email: Result<string>}> */
    public function default(): NestedResult
    {
         
        return NestedResult::forValidValue($this->key(), $this->rules->createValidResultSet(['name' => '', 'email' => '']);
    }

    /** @return NestedResult<ResultSet{name: Result<string>, email: Result<string>}> */
    public function missing(): NestedResult
    {
        return NestedResult::forInvalidValue(
            $this->key(),
            new ResultSet(
                Result::forMissingValue('name'),
                Result::forMissingValue('email'),
            ),
            'The author struct was missing'
        );
    }
};
```

Note that the returned `NestedResult` from each of `validate()`, `default()`, and `missing()` always contains a `ResultSet` as its value.

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

-----

- [Back to Table of Contents](../README.md)
