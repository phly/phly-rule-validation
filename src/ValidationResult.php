<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

interface ValidationResult
{
    /** @psalm-return non-empty-string */
    public function key(): string;

    public function isValid(): bool;

    public function value(): mixed;

    public function message(): ?string;
}
