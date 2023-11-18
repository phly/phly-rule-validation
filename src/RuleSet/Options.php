<?php

declare(strict_types=1);

namespace Phly\RuleValidation\RuleSet;

use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\ValidationResult;

interface Options
{
    /** @return class-string<ResultSet> */
    public function resultSetClass(): string;

    /** @return callable(non-empty-string): ValidationResult */
    public function missingValueResultFactory(): callable;

    /** @return Rule[] */
    public function rules(): array;
}
