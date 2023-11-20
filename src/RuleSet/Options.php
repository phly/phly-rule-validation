<?php

declare(strict_types=1);

namespace Phly\RuleValidation\RuleSet;

use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\Rule;

interface Options
{
    /** @return class-string<ResultSet> */
    public function resultSetClass(): string;

    /** @return Rule[] */
    public function rules(): array;
}
