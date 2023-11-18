<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Result;

use Phly\RuleValidation\ValidationResult;

interface CreateMissingValueResult
{
    /** @param non-empty-string $key */
    public function __invoke(string $key): ValidationResult;
}
