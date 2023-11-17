<?php

declare(strict_types=1);

namespace Phly\RuleValidation\RuleSet;

use Phly\RuleValidation\Exception\DuplicateRuleKeyException;
use Phly\RuleValidation\Exception\RequiredRuleWithNoDefaultValueException;
use Phly\RuleValidation\Result;
use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\RuleSetValidator;
use Phly\RuleValidation\ValidationResult;

use function array_key_exists;

/**
 * @template T of ResultSet
 * @template-implements IteratorAggregate<Rule>
 */
class RuleSet implements RuleSetValidator
{
    /** @var class-string<T> */
    protected string $resultSetClass = ResultSet::class;

    /** @var array<string, Rule> */
    private array $rules = [];

    /**
     * Create a Result representing a missing value
     *
     * Override this method to customize the message used for individual (or all)
     * missing values.
     *
     * @psalm-param non-empty-string $key
     */
    public function createMissingValueResultForKey(string $key): ValidationResult
    {
        return Result::forMissingValue($key);
    }

    /**
     * @param class-string<ResultSet> $resultSetClass
     * @return RuleSet
     */
    final public static function createWithResultSetClass(string $resultSetClass, Rule ...$rules): self
    {
        $ruleSet                 = new static(...$rules);
        $ruleSet->resultSetClass = $resultSetClass;

        return $ruleSet;
    }

    final public function __construct(Rule ...$rules)
    {
        foreach ($rules as $rule) {
            $this->add($rule);
        }
    }

    final public function add(Rule $rule): void
    {
        $key = $rule->key();
        $this->guardForDuplicateKey($key);
        $this->rules[$key] = $rule;
    }

    final public function getRule(string $key): ?Rule
    {
        foreach ($this->rules as $rule) {
            if ($rule->key() === $key) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * @param array<non-empty-string, mixed> $data
     * @return T
     */
    final public function validate(array $data): ResultSet
    {
        $resultSet = new ($this->resultSetClass)();

        foreach ($this->rules as $rule) {
            $key = $rule->key();
            /** @psalm-var non-empty-string $key */
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
     * @return T
     */
    final public function createValidResultSet(array $valueMap = []): ResultSet
    {
        $resultSet = new ($this->resultSetClass)();

        foreach ($this->rules as $rule) {
            $key = $rule->key();
            /** @psalm-var non-empty-string $key */
            if (array_key_exists($key, $valueMap)) {
                $resultSet->add(Result::forValidValue($key, $valueMap[$key]));
                continue;
            }

            if ($rule->required() && null === $rule->default()) {
                throw RequiredRuleWithNoDefaultValueException::forKey($key, ResultSet::class);
            }

            $resultSet->add(Result::forValidValue($key, $rule->default()));
        }

        return $resultSet;
    }

    /** @throws Exception\DuplicateRuleKeyException */
    private function guardForDuplicateKey(string $key): void
    {
        if (array_key_exists($key, $this->rules)) {
            throw DuplicateRuleKeyException::forKey($key);
        }
    }
}
