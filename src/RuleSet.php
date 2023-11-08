<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use Ramsey\Collection\AbstractCollection;

/**
 * @extends AbstractCollection<Rule>
 */
class RuleSet extends AbstractCollection
{
    public function getType(): string
    {
        return Rule::class;
    }

    public function getRuleForKey(string $key): ?Rule
    {
        foreach ($this as $rule) {
            if ($rule->for() === $key) {
                return $rule;
            }
        }
        return null;
    }
}
