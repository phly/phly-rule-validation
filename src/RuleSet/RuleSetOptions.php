<?php

declare(strict_types=1);

namespace Phly\RuleValidation\RuleSet;

use Phly\RuleValidation\Result;
use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\ValidationResult;

use function is_callable;

class RuleSetOptions implements Options
{
    /** @var class-string<ResultSet> */
    private string $resultSetClass = ResultSet::class;

    /** @var null|callable(non-empty-string): ValidationResult */
    private $missingValueResultFactory;

    /** @var Rule[] */
    private array $rules = [];

    /** @return class-string<ResultSet> */
    public function resultSetClass(): string
    {
        return $this->resultSetClass;
    }

    /** @return callable(non-empty-string): ValidationResult */
    public function missingValueResultFactory(): callable
    {
        if (! is_callable($this->missingValueResultFactory)) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            return /** @param non-empty-string $key */ fn (string $key): ValidationResult => Result::forMissingValue($key);
        }

        return $this->missingValueResultFactory;
    }

    /** @return Rule[] */
    public function rules(): array
    {
        return $this->rules;
    }

    /** @param class-string<ResultSet> $class */
    public function setResultSetClass(string $class): void
    {
        $this->resultSetClass = $class;
    }

    /** @param callable(non-empty-string): ValidationResult $factory */
    public function setMissingValueResultFactory(callable $factory): void
    {
        $this->missingValueResultFactory = $factory;
    }

    public function addRule(Rule $rule): void
    {
        $this->rules[] = $rule;
    }
}
