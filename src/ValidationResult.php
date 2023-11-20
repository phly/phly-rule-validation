<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

/**
 * @template T
 */
interface ValidationResult
{
    /** @psalm-return non-empty-string */
    public function key(): string;

    public function isValid(): bool;

    /** @return T */
    public function value(): mixed;

    public function message(): ?string;
}
