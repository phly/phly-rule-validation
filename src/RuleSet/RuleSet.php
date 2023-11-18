<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols, PSR12.Files.FileHeader.SpacingAfterBlock, PSR12.Files.FileHeader.IncorrectOrder, SlevomatCodingStandard.TypeHints.DeclareStrictTypes.IncorrectWhitespaceBeforeDeclare

declare(strict_types=1);

namespace Phly\RuleValidation\RuleSet;

use Phly\RuleValidation\Exception\DuplicateRuleKeyException;
use Phly\RuleValidation\Exception\RequiredRuleWithNoDefaultValueException;
use Phly\RuleValidation\Result\CreateMissingValueResult;
use Phly\RuleValidation\Result\Result;
use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\RuleSetValidator;

use function array_key_exists;

/**
 * @template T of ResultSet
 * @template-implements RuleSetValidator<T>
 */
readonly class RuleSet implements RuleSetValidator
{
    /** @var class-string<T> */
    private string $resultSetClass;

    private CreateMissingValueResult $missingValueResultFactory;

    /** @var array<string, Rule> */
    private array $rules;

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
        $results = [];

        foreach ($this->rules as $rule) {
            $key = $rule->key();
            /** @psalm-var non-empty-string $key */
            if (array_key_exists($key, $valueMap)) {
                $results[] = Result::forValidValue($key, $valueMap[$key]);
                continue;
            }

            if ($rule->required() && null === $rule->default()) {
                throw RequiredRuleWithNoDefaultValueException::forKey($key, ResultSet::class);
            }

            $results[] = Result::forValidValue($key, $rule->default());
        }

        return new ($this->resultSetClass)(...$results);
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
        $createMissingValueResult = $this->missingValueResultFactory;
        $results                  = [];

        foreach ($this->rules as $rule) {
            $key = $rule->key();
            /** @psalm-var non-empty-string $key */
            if (array_key_exists($key, $data)) {
                $results[] = $rule->validate($data[$key], $data);
                continue;
            }

            if ($rule->required()) {
                $results[] = $createMissingValueResult($key);
                continue;
            }

            $results[] = Result::forValidValue($key, $rule->default());
        }

        return new ($this->resultSetClass)(...$results);
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
