# phly/phly-rule-validation

This library provides a barebones validation framework for use with web forms and API payloads.

Users will define _rule sets_, which produce _result sets_.
A _rule set_ is composed of one or more _rules_, each tied to a specific key in the data set under validation.
Each rule will produce a _result_ on validation, and these results are aggregated into the _result set_.

## Goals of this library

The explicit goals of this library are:

- Provide an idempotent way to validate individual items and/or data sets.
- Provide an extensible framework for developing validation rules, results, and rule and result sets.
- Allow handling optional data, with default values.
- Allow reporting validation error messages.
- Ensure missing required values are reported as validation failures.
- Use as few dependencies as possible.

Non-goals:

- Creating an extensive set of validation rule classes.
- Providing extensive mechanisms for validating and returning nested data sets. (Note: basic support for nested data sets is provided, but it is up to consumers to wire them.)
- Providing a configuration-driven mechanism for creating rule sets.
- Providing HTML form input representations or all metadata required to create HTML form input representations.

## Table of Contents

- [Installation](./installation.md)
- [Basic Usage](./usage.md)
- [Rule Validation Reference](./reference/README.md)
  - [Results](./reference/result.md)
  - [ResultSets](./reference/result-set.md)
  - [Defining Rules](./reference/rule.md)
  - [Shipped Rules](./reference/shipped-rules.md)
  - [RuleSets](./reference/rule-set.md)
- [Additional Topics](./advanced/README.md)
  - [Providing Type Information](./advanced/types.md)
  - [Handling Missing Values](./advanced/missing-value.md)
  - [Nested Results](./advanced/nested-result.md)
