<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

interface Rule
{
    public function required(): bool;

    /** Return the key the Rule applies to */
    public function for(): string;

    /** Validate the value */
    public function validate(mixed $value, array $context): Result;

    /** Default value to use when not required and no value provided */
    public function default(): mixed;
}
