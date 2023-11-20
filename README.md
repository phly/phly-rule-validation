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

## Basic Usage

```php
use Phly\RuleValidation\Result\Result;
use Phly\RuleValidation\RuleSet\RuleSet;
use Phly\RuleValidation\Rule\CallbackRule;

$rules = new RuleSet();
$rules->add(new CallbackRule('flag', function (mixed $value, array $data): Result {
    if (! is_bool($value)) {
        return Result::forInvalidValue('flag', $value, 'Not a boolean value');
    }
    return Result::forValidValue('flag', $value);
}, default: false));
$rules->add(new MyCustomRule());
// and so on

$resultSet = $rules->validate($someFormData);

if ($resultSet->isValid()) {
    $values = $resultSet->getValues();
    // do something with values
} else {
    $messages = $resultSet->getMessages();
    // do something with error messages
}

// Get a result for a single key:
$flagResult = $resultSet->flag; // or $resultSet->getResult('flag')

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

## Documentation

Please see the [documentation tree (docs/)](./docs/README.md).
