# Result Sets

A result set aggregates validation results, and is represented via a `Phly\RuleValidation\ResultSet` instance, which defines the following:

```php
/**
 * @template-implements IteratorAggregate<ValidationResult>
 */
class ResultSet implements Countable, IteratorAggregate
{
    final public function __construct(ValidationResult ...$results);

    final public function __isset(string $name): bool;
    final public function __get(string $key): ?ValidationResult;
    final public function count(): int;
    /** @return Traversable<ValidationResult> */
    final public function getIterator(): Traversable

    /** @throws Exception\UnknownResultException */
    final public function getResult(string $key): ValidationResult;

    final public function isValid(): bool;

    /** @return array<array-key, null|string> */
    final public function getMessages(): array;
    /** @return array<string, mixed> */
    final public function getValues(): array;
}
```

Things to note:

- A `ResultSet` is _iterable_; iterating through it produces `ValidationResult` instances.
  You can also _count_ the number of results.
- You may access individual `ValidationResult` instances by direct property access, using the key associated with the result.
- You can request key/value pairs of result values.
  This will return the same number of values as the number of validation results it composes.
- You can request key/message pairs of error messages.
  This will only return pairs for failed validation results.

> #### Why is everything marked final?
>
> You'll note that all methods are marked final.
> If that's the case, why is the class not marked final instead?
>
> The only reason to extend the class is to provide type information for the various result instances composed by it.
> We cover this in the [Providing Type Information](../advanced/providing-type-information.md) section.

## Usage within PHP Code

What do you do once you have a result set?

First, you could decide whether to redisplay a form based on validity of the result set:

```php
if (! $resultSet->isValid()) {
    $response = $responseFactory->createResponse(400)->withHeader('Content-Type', 'text/html');
    $response->getBody()->write($template->render('app::form', [
        'form' => $resultSet,
    ]);
    return $response;
}
```

Or you could populate an API problem details response:

```php
if (! $resultSet->isValid()) {
    $response = $responseFactory->createResponse(400)->withHeader('Content-Type', 'application/problem+json');
    $response->getBody()->write(json_encode([
        'type' => 'https://example.com/api/errors/validation-error',
        'title' => 'Invalid payload provided',
        'invalid-params' => $resultSet->getMessages(),
    ]));
    return $response;
}
```

Alternately, if the result set is valid, you might use that data to populate an entity instance, and then do something with that:

```php
if ($resultSet->isValid()) {
    $entity = SomeRelatedEntity::populateFromResultSet($resultSet);
    $repository->update($entity);
}
```

## Usage in HTML Form Templates

One key use case for a `ResultSet` is for populating an HTML form.
The following example uses [Plates](https://platesphp.com), but you may easily adapt it to other template solutions.

```php
<?php /** @var ResultSet $form */ */
<?php if (! $form->isValid()): ?>
<p class="text-warning">
    One or more submissions were invalid; please check for error messages
    below, and resolve the issues before resubmitting.
</p>
<?php endif ?>
<input type="text" name="title" value="<?= $this->e($form->title->value()) ?>">
<?php if (! $form->title->isValid()): ?>
<p class="form-error"><?= $this->e($form->title->message()) ?></p>
<?php endif ?>
<!-- ... -->
```

-----

- [Back to Table of Contents](../README.md)
