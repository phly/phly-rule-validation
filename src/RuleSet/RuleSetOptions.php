<?php

declare(strict_types=1);

namespace Phly\RuleValidation\RuleSet;

use Phly\RuleValidation\Result\CreateMissingValueResult;
use Phly\RuleValidation\Result\MissingValueResultFactory;
use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\Rule;

class RuleSetOptions implements Options
{
    /** @var class-string<ResultSet> */
    private string $resultSetClass = ResultSet::class;

    /** @var null|CreateMissingValueResult */
    private $missingValueResultFactory;

    /** @var Rule[] */
    private array $rules = [];

    /** @return class-string<ResultSet> */
    public function resultSetClass(): string
    {
        return $this->resultSetClass;
    }

    public function missingValueResultFactory(): CreateMissingValueResult
    {
        if (null === $this->missingValueResultFactory) {
            return new MissingValueResultFactory();
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

    public function setMissingValueResultFactory(CreateMissingValueResult $factory): void
    {
        $this->missingValueResultFactory = $factory;
    }

    public function addRule(Rule $rule): void
    {
        $this->rules[] = $rule;
    }
}
