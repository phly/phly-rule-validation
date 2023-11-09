<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use InvalidArgumentException;
use Ramsey\Collection\AbstractCollection;

use function array_key_exists;
use function get_debug_type;
use function sprintf;

/**
 * @extends AbstractCollection<Rule>
 */
class RuleSet extends AbstractCollection
{
    public function getType(): string
    {
        return Rule::class;
    }

    final public function offsetSet(mixed $offset, mixed $value): void
    {
        if (! $value instanceof Rule) {
            throw new InvalidArgumentException(sprintf(
                '%s expects all values to be of type %s; received %s',
                self::class,
                Rule::class,
                get_debug_type($value),
            ));
        }

        $this->guardForDuplicateKey($value->key());

        parent::offsetSet($offset, $value);
    }

    final public function getRuleForKey(string $key): ?Rule
    {
        foreach ($this as $rule) {
            if ($rule->key() === $key) {
                return $rule;
            }
        }
        return null;
    }

    final public function validate(array $data): ResultSet
    {
        $resultSet = new ResultSet();

        foreach ($this as $rule) {
            $key = $rule->key();
            if (array_key_exists($key, $data)) {
                $resultSet[$key] = $rule->validate($data[$key], $data);
                continue;
            }

            if ($rule->required()) {
                $resultSet[$key] = $this->createMissingValueResultForKey($key);
                continue;
            }

            $resultSet[$key] = Result::forValidValue($rule->default());
        }

        return $resultSet;
    }

    /**
     * Create a Result representing a missing value
     *
     * Override this method to customize the message used for individual (or all)
     * missing values.
     */
    public function createMissingValueResultForKey(string $key): Result
    {
        return Result::forMissingValue();
    }

    /** @throws Exception\DuplicateRuleKeyException */
    private function guardForDuplicateKey(string $key): void
    {
        foreach ($this as $rule) {
            /** @var Rule $rule */
            if ($rule->key() === $key) {
                throw Exception\DuplicateRuleKeyException::forKey($key);
            }
        }
    }
}
