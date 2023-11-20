<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Result;

use OutOfRangeException;
use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\ValidationResult;
use UnexpectedValueException;

use function get_class;
use function sprintf;

/**
 * Result extension representing a nested result.
 *
 * A NestedResult allows access to Result instances nested inside a ResultSet.
 *
 * <code>
 * // in a Rule implementation:
 * public function validate(mixed $value, array $context): Result
 * {
 *     $resultSet = $this->ruleSet->validate($value, $data);
 *     return NestedResult::forValidValue($this->key(), $resultSet);
 * </code>
 *
 * This allows more intuitive access to the results in a nested result set:
 *
 * <code>
 * // Where $form is a ResultSet, and the $author property is a NestedResult,
 * // with a ResultSet containing "name" and "email" properties:
 * if ($form->author->isValid) {
 *     new Author($form->author->name->value, $form->author->email->value);
 * }
 * </code>
 *
 * Values provided to NestedResult MUST be ResultSet instances.
 *
 * @template T of ResultSet
 * @template-implements ValidationResult<T>
 */
class NestedResult implements ValidationResult
{
    public const MISSING_MESSAGE = 'Missing required value';

    /** @psalm-param non-empty-string $name */
    public function __isset(string $name): bool
    {
        return isset($this->value->$name);
    }

    /**
     * @psalm-param non-empty-string $name
     * @throws OutOfRangeException When the value property is not a ResultSet,
     *     or the given $name is not a known property of the ResultSet.
     */
    public function __get(string $name): ValidationResult
    {
        if (! isset($this->value->$name)) {
            throw new OutOfRangeException(sprintf(
                '%s instance composed by %s does not contain a "%s" result',
                get_class($this->value),
                self::class,
                $name,
            ));
        }

        $value = $this->value->$name;

        if (! $value instanceof ValidationResult) {
            throw new UnexpectedValueException(sprintf(
                'Value associated with "%s" is not a %s instance',
                $name,
                ValidationResult::class,
            ));
        }

        return $value;
    }

    /**
     * @template R of ResultSet
     * @psalm-param non-empty-string $key
     * @psalm-param R $value
     * @return self<R>
     */
    public static function forValidValue(string $key, ResultSet $value): self
    {
        /** @var self<R> $result */
        $result = new self(key: $key, isValid: true, value: $value);

        return $result;
    }

    /**
     * @psalm-param non-empty-string $key
     */
    public static function forInvalidValue(string $key, ResultSet $value, string $message): self
    {
        return new self(key: $key, isValid: false, value: $value, message: $message);
    }

    /**
     * @psalm-param non-empty-string $key
     * @return self<ResultSet>
     */
    public static function forMissingValue(string $key, string $message = self::MISSING_MESSAGE): self
    {
        $resultSet = new ResultSet();
        return new self(key: $key, isValid: false, value: $resultSet, message: $message);
    }

    /** @return non-empty-string */
    public function key(): string
    {
        return $this->key;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    /** @return T */
    public function value(): mixed
    {
        return $this->value;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    private function __construct(
        /** @var non-empty-string */
        private string $key,
        private bool $isValid,
        /** @var T */
        private ResultSet $value,
        private ?string $message = null,
    ) {
    }
}
