<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function array_key_exists;

/** @template-implements IteratorAggregate<Rule> */
class RuleSet implements IteratorAggregate
{
    /** @var array<string, Rule> */
    private array $rules = [];

    /**
     * Create a Result representing a missing value
     *
     * Override this method to customize the message used for individual (or all)
     * missing values.
     */
    public function createMissingValueResultForKey(string $key): Result
    {
        return Result::forMissingValue($key);
    }

    final public function __construct(Rule ...$rules)
    {
        foreach ($rules as $rule) {
            $this->add($rule);
        }
    }

    /** @return Traversable<Rule> */
    final public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rules);
    }

    final public function add(Rule $rule): void
    {
        $key = $rule->key();
        $this->guardForDuplicateKey($key);
        $this->rules[$key] = $rule;
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

        foreach ($this->rules as $rule) {
            $key = $rule->key();
            if (array_key_exists($key, $data)) {
                $resultSet->add($rule->validate($data[$key], $data));
                continue;
            }

            if ($rule->required()) {
                $resultSet->add($this->createMissingValueResultForKey($key));
                continue;
            }

            $resultSet->add(Result::forValidValue($key, $rule->default()));
        }

        $resultSet->freeze();
        return $resultSet;
    }

    /**
     * @param array<non-empty-string, mixed> $valueMap
     * @param class-string<ResultSet> $resultSetClass
     */
    final public function createValidResultSet(
        array $valueMap = [],
        string $resultSetClass = ResultSet::class
    ): ResultSet {
        $resultSet = new $resultSetClass();

        foreach ($this->rules as $rule) {
            $key = $rule->key();
            /** @var non-empty-string $key */
            if (array_key_exists($key, $valueMap)) {
                $resultSet->add(Result::forValidValue($key, $valueMap[$key]));
                continue;
            }

            if ($rule->required() && null === $rule->default()) {
                throw Exception\RequiredRuleWithNoDefaultValueException::forKey($key, ResultSet::class);
            }

            $resultSet->add(Result::forValidValue($key, $rule->default()));
        }

        return $resultSet;
    }

    /** @throws Exception\DuplicateRuleKeyException */
    private function guardForDuplicateKey(string $key): void
    {
        if (array_key_exists($key, $this->rules)) {
            throw Exception\DuplicateRuleKeyException::forKey($key);
        }
    }
}
