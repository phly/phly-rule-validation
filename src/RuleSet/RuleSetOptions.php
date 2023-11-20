<?php

declare(strict_types=1);

namespace Phly\RuleValidation\RuleSet;

use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\Rule;

class RuleSetOptions implements Options
{
    /** @var class-string<ResultSet> */
    private string $resultSetClass = ResultSet::class;

    /** @var Rule[] */
    private array $rules = [];

    /** @return class-string<ResultSet> */
    public function resultSetClass(): string
    {
        return $this->resultSetClass;
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

    public function addRule(Rule $rule): void
    {
        $this->rules[] = $rule;
    }
}
