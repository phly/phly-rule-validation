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
 * @template-implements RuleSetValidator<T>
 */
class RuleSet implements RuleSetValidator
{
    /** @var class-string<T> */
    private readonly string $resultSetClass;

    /**
     * @readonly
     * @var callable(non-empty-string): ValidationResult
     */
    private $missingValueResultFactory;

    /** @var array<string, Rule> */
    private array $rules = [];

    /** @return self<ResultSet> */
    public static function createWithRules(Rule ...$rules): self
    {
        $options = new RuleSetOptions();
        foreach ($rules as $rule) {
            $options->addRule($rule);
        }

        return new self($options);
    }

    public function __construct(Options $options)
    {
        /** @todo Remove suppression once Psalm can match concrete class name to template */
        /** @psalm-suppress PropertyTypeCoercion */
        $this->resultSetClass            = $options->resultSetClass();
        $this->missingValueResultFactory = $options->missingValueResultFactory();

        $rules = [];
        foreach ($options->rules() as $rule) {
            $key = $rule->key();
            $this->guardForDuplicateKey($key, $rules);
            $rules[$key] = $rule;
        }
        $this->rules = $rules;
    }

    /**
     * @param array<non-empty-string, mixed> $valueMap
     * @return T
     */
    public function createValidResultSet(array $valueMap = []): ResultSet
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

    /** @param non-empty-string $key */
    final public function getRule(string $key): ?Rule
    {
        return array_key_exists($key, $this->rules) ? $this->rules[$key] : null;
    }

    /**
     * @param array<non-empty-string, mixed> $data
     * @return T
     */
    final public function validate(array $data): ResultSet
    {
        $missingValueResultFactory = $this->missingValueResultFactory;
        $resultSet                 = new ($this->resultSetClass)();

        foreach ($this->rules as $rule) {
            $key = $rule->key();
            /** @psalm-var non-empty-string $key */
            if (array_key_exists($key, $data)) {
                $resultSet->add($rule->validate($data[$key], $data));
                continue;
            }

            if ($rule->required()) {
                $resultSet->add($missingValueResultFactory($key));
                continue;
            }

            $resultSet->add(Result::forValidValue($key, $rule->default()));
        }

        $resultSet->freeze();
        return $resultSet;
    }

    /**
     * @param non-empty-string $key
     * @param array<string, Rule> $rules
     * @throws DuplicateRuleKeyException
     */
    private function guardForDuplicateKey(string $key, array $rules): void
    {
        if (array_key_exists($key, $rules)) {
            throw DuplicateRuleKeyException::forKey($key);
        }
    }
}
