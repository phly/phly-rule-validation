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

    public function offsetSet(mixed $offset, mixed $value): void
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

    public function getRuleForKey(string $key): ?Rule
    {
        foreach ($this as $rule) {
            if ($rule->key() === $key) {
                return $rule;
            }
        }
        return null;
    }

    public function validate(array $data): ResultSet
    {
        $resultSet = new ResultSet();

        foreach ($this as $rule) {
            $key = $rule->key();
            if (array_key_exists($key, $data)) {
                $resultSet[$key] = $rule->validate($data[$key], $data);
                continue;
            }

            if ($rule->required()) {
                $resultSet[$key] = Result::forMissingValue('Missing required value for key ' . $key);
                continue;
            }

            $resultSet[$key] = Result::forValidValue($rule->default());
        }

        return $resultSet;
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
