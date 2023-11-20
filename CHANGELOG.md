# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.2.0 - 2023-11-20

### Added

- Adds support for PHP 8.3.

- Adds `ValidationResult` interface, defining the key, value, validity, and error messages for a validation result.
- Adds `RuleSetValidator` interface, defining how to validate a rule set, create a default initial valid result set based on the rule set, and retrieve individual rules from the rule set.
- Adds the `Phly\RuleValidation\RuleSet\Options` interface.
- Adds the `Phly\RuleValidation\RuleSet\RuleSetOptions` implementation.
- Adds the `Phly\RuleValidation\Result\NestedResult` class, defining a result that has a `ResultSet` as a value.
- Adds [comprehensive documentation](./docs/README.md).

### Changed

- The `Rule` interface:
  - adds:
    - a `missing(): ValidationResult` method.
  - modifies:
    - `default()`; it now returns a `ValidationResult` instance.
    - `key()`; it now expects a `non-empty-string`.
    - `validate()`; it now returns a `ValidationResult` instance, and requires `$context` to have `non-empty-string` keys.
- Renames `BooleanRule` to `Phly\RuleValidation\Rule\BooleanRule`, and updates it to the new `Rule` requirements.
- Renames `CallbackRule` to `Phly\RuleValidation\Rule\CallbackRule`, and updates it to the new `Rule` requirements.
- The `RuleSet` class:
  - Renames `RuleSet` to `Phly\RuleValidation\RuleSet\RuleSet`.
  - Now implements `RuleSetValidator`.
  - Is now idempotent; there is no `addRule()` method.
  - The only ways to create an instance are now `__construct(Options $options)` and `createWithRules(Rule ...$rules)`.
  - It is no longer iterable.
  - `getRuleForKey()` is renamed to `getRule()`.
  - It adds `__isset()` and `__get()` implementations for access to named rules.
- The `ResultSet` class:
  - Now implements `Countable`.
  - Is now idempotent; there is no `add()` method. All results must be provided to the constructor.
  - All methods are now marked `final`. Extension only allows providing property type hints for the purpose of providing hints for composed validation results.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.1 - 2023-11-10

### Added

- Adds a `__get()` implementation to `ResultSet` to allow access to composed `Result` instances via property access.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.0 - 2023-11-09

### Added

- Adds functionality for initial stable release

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
