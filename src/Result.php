<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols,SlevomatCodingStandard.TypeHints.DeclareStrictTypes.IncorrectWhitespaceBeforeDeclare

declare(strict_types=1);

namespace Phly\RuleValidation;

// @todo Remove file-level phpcs:disable once PSR1 ruleset understands readonly classes

/**
 * @template T
 */
final readonly class Result
{
    public const MISSING_MESSAGE = 'Missing required value';

    private function __construct(
        public string $key,
        public bool $isValid,
        /** @var T */
        public mixed $value,
        public ?string $message = null,
    ) {
    }

    /**
     * @return Result<T>
     */
    public static function forValidValue(string $key, mixed $value): self
    {
        return new self(key: $key, isValid: true, value: $value);
    }

    public static function forInvalidValue(string $key, mixed $value, string $message): self
    {
        return new self(key: $key, isValid: false, value: $value, message: $message);
    }

    public static function forMissingValue(string $key, string $message = self::MISSING_MESSAGE): self
    {
        return new self(key: $key, isValid: false, value: null, message: $message);
    }
}
