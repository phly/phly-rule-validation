<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation;

use Phly\RuleValidation\Result;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\RuleSet;
use PHPUnit\Framework\TestCase;

class RuleSetTest extends TestCase
{
    public function testRuleSetCollectsRules(): void
    {
        $resultSet = new RuleSet();
        $this->assertSame(Rule::class, $resultSet->getType());
    }

    public function testGetRuleForKeyReturnsFirstRuleMatchingKey(): void
    {
        $rule1 = $this->createDummyRuleWithName('first');
        $rule2 = $this->createDummyRuleWithName('second');
        $rule3 = $this->createDummyRuleWithName('second');
        $rule4 = $this->createDummyRuleWithName('fourth');
        $rule5 = $this->createDummyRuleWithName('first');

        $ruleSet = new RuleSet([$rule1, $rule2, $rule3, $rule4]);

        $this->assertSame($rule1, $ruleSet->getRuleForKey('first'));
        $this->assertSame($rule2, $ruleSet->getRuleForKey('second'));
    }

    public function testGetRuleForKeyReturnsNullIfNoRuleMatchingKeyFound(): void
    {
        $rule1 = $this->createDummyRuleWithName('first');
        $rule2 = $this->createDummyRuleWithName('second');

        $ruleSet = new RuleSet([$rule1, $rule2]);

        $this->assertNull($ruleSet->getRuleForKey('fourth'));
    }

    private function createDummyRuleWithName(string $name): Rule
    {
        return new class ($name) implements Rule {
            public function __construct(
                private string $name,
            ) {
            }

            public function required(): bool
            {
                return false;
            }

            public function for(): string
            {
                return $this->name;
            }

            public function validate(mixed $value, array $context): Result
            {
                return Result::forValidValue($value);
            }

            public function default(): mixed
            {
                return null;
            }
        };
    }
}
