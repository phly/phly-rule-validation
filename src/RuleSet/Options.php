<?php

declare(strict_types=1);

namespace Phly\RuleValidation\RuleSet;

use Phly\RuleValidation\Result\CreateMissingValueResult;
use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\Rule;

interface Options
{
    /** @return class-string<ResultSet> */
    public function resultSetClass(): string;

    public function missingValueResultFactory(): CreateMissingValueResult;

    /** @return Rule[] */
    public function rules(): array;
}
