<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation;

use Phly\RuleValidation\Exception\DuplicateRuleKeyException;
use Phly\RuleValidation\Result;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\RuleSet;
use PHPUnit\Framework\TestCase;

class RuleSetTest extends TestCase
{
    public function testRuleSetCollectsRules(): void
    {
        $ruleSet = new RuleSet();
        $this->assertSame(Rule::class, $ruleSet->getType());
    }

    public function testCallingAddWithARuleWithANameUsedByAnotherRuleInTheRuleSetRaisesDuplicateKeyException(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRuleWithName('first'));

        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        $ruleSet->add($this->createDummyRuleWithName('first'));
    }

    public function testAppendingRuleWithANameUsedByAnotherRuleInTheRuleSetRaisesDuplicateKeyException(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRuleWithName('first'));

        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        $ruleSet[] = $this->createDummyRuleWithName('first');
    }

    public function testAddingRuleByOffsetWithANameUsedByAnotherRuleInTheRuleSetRaisesDuplicateKeyException(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRuleWithName('first'));

        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        $ruleSet['second'] = $this->createDummyRuleWithName('first');
    }

    public function testInstantiatingRuleSetWithRulesForSameKeyRaisesDuplicateKeyException(): void
    {
        $rule1 = $this->createDummyRuleWithName('first');
        $rule2 = $this->createDummyRuleWithName('first');

        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        new RuleSet([$rule1, $rule2]);
    }

    public function testGetRuleForKeyReturnsRuleMatchingKey(): void
    {
        $rule1 = $this->createDummyRuleWithName('first');
        $rule2 = $this->createDummyRuleWithName('second');
        $rule3 = $this->createDummyRuleWithName('third');
        $rule4 = $this->createDummyRuleWithName('fourth');

        $ruleSet = new RuleSet([$rule2, $rule3, $rule1, $rule4]);

        $this->assertSame($rule1, $ruleSet->getRuleForKey('first'));
        $this->assertSame($rule2, $ruleSet->getRuleForKey('second'));
        $this->assertSame($rule3, $ruleSet->getRuleForKey('third'));
        $this->assertSame($rule4, $ruleSet->getRuleForKey('fourth'));
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

            public function key(): string
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
