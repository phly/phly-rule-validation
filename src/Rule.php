<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

interface Rule
{
    public function required(): bool;

    /**
     * Return the key the Rule applies to
     *
     * @return non-empty-string
     */
    public function key(): string;

    /**
     * Validate the value
     *
     * @param array<non-empty-string, mixed> $context
     */
    public function validate(mixed $value, array $context): ValidationResult;

    /** ValidationResult to use when not required and no value provided */
    public function default(): ValidationResult;

    /** ValidationResult to use when required but no value provided */
    public function missing(): ValidationResult;
}
