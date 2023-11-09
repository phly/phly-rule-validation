<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols,SlevomatCodingStandard.TypeHints.DeclareStrictTypes.IncorrectWhitespaceBeforeDeclare

declare(strict_types=1);

namespace Phly\RuleValidation;

// @todo Remove file-level phpcs:disable once PSR1 ruleset understands readonly classes
final readonly class Result
{
    public const MISSING_MESSAGE = 'Missing required value';

    private function __construct(
        public bool $isValid,
        public mixed $value,
        public ?string $message = null,
    ) {
    }

    public static function forValidValue(mixed $value): self
    {
        return new self(isValid: true, value: $value);
    }

    public static function forInvalidValue(mixed $value, string $message): self
    {
        return new self(isValid: false, value: $value, message: $message);
    }

    public static function forMissingValue(string $message = self::MISSING_MESSAGE): self
    {
        return new self(isValid: false, value: null, message: $message);
    }
}
