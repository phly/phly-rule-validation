<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use InvalidArgumentException;
use Ramsey\Collection\AbstractCollection;

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
