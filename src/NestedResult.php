<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use OutOfRangeException;
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
 * @template N
 * @template-extends Result<N>
 */
class NestedResult extends Result
{
    /** @psalm-param non-empty-string $name */
    public function __isset(string $name): bool
    {
        if (! $this->value instanceof ResultSet) {
            return false;
        }

        return isset($this->value->$name);
    }

    /**
     * @psalm-param non-empty-string $name
     * @throws OutOfRangeException When the value property is not a ResultSet,
     *     or the given $name is not a known property of the ResultSet.
     */
    public function __get(string $name): Result
    {
        if (! $this->value instanceof ResultSet) {
            throw new OutOfRangeException(sprintf(
                '%s instance does not compose a %s value; property access not available',
                self::class,
                ResultSet::class,
            ));
        }

        if (! isset($this->value->$name)) {
            throw new OutOfRangeException(sprintf(
                '%s instance composed by %s does not contain a "%s" result',
                get_class($this->value),
                self::class,
                $name,
            ));
        }

        $value = $this->value->$name;

        if (! $value instanceof Result) {
            throw new UnexpectedValueException(sprintf(
                'Value associated with "%s" is not a %s instance',
                $name,
                Result::class,
            ));
        }

        return $value;
    }
}
