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
        $ruleSet->add($this->createDummyRule('first'));

        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        $ruleSet->add($this->createDummyRule('first'));
    }

    public function testAppendingRuleWithANameUsedByAnotherRuleInTheRuleSetRaisesDuplicateKeyException(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRule('first'));

        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        $ruleSet[] = $this->createDummyRule('first');
    }

    public function testAddingRuleByOffsetWithANameUsedByAnotherRuleInTheRuleSetRaisesDuplicateKeyException(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRule('first'));

        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        $ruleSet['second'] = $this->createDummyRule('first');
    }

    public function testInstantiatingRuleSetWithRulesForSameKeyRaisesDuplicateKeyException(): void
    {
        $rule1 = $this->createDummyRule('first');
        $rule2 = $this->createDummyRule('first');

        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        new RuleSet([$rule1, $rule2]);
    }

    public function testGetRuleForKeyReturnsRuleMatchingKey(): void
    {
        $rule1 = $this->createDummyRule('first');
        $rule2 = $this->createDummyRule('second');
        $rule3 = $this->createDummyRule('third');
        $rule4 = $this->createDummyRule('fourth');

        $ruleSet = new RuleSet([$rule2, $rule3, $rule1, $rule4]);

        $this->assertSame($rule1, $ruleSet->getRuleForKey('first'));
        $this->assertSame($rule2, $ruleSet->getRuleForKey('second'));
        $this->assertSame($rule3, $ruleSet->getRuleForKey('third'));
        $this->assertSame($rule4, $ruleSet->getRuleForKey('fourth'));
    }

    public function testGetRuleForKeyReturnsNullIfNoRuleMatchingKeyFound(): void
    {
        $rule1 = $this->createDummyRule('first');
        $rule2 = $this->createDummyRule('second');

        $ruleSet = new RuleSet([$rule1, $rule2]);

        $this->assertNull($ruleSet->getRuleForKey('fourth'));
    }

    public function testValidationReturnsAnEmptyResultSetWhenNoRulesPresent(): void
    {
        $ruleSet = new RuleSet();
        $result  = $ruleSet->validate(['some' => 'data']);
        $this->assertCount(0, $result);
    }

    public function testValidationReturnsAPopulatedResultSetWithAKeyMatchingEachRule(): void
    {
        $data = [
            'first'  => 'string',
            'second' => 'ignored',
            'third'  => 1,
            'fourth' => 'also ignored',
            'fifth'  => [1, 2, 3],
        ];

        $expected = [
            'first' => 'string',
            'third' => 1,
            'fifth' => [1, 2, 3],
        ];

        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRule('first'));
        $ruleSet->add($this->createDummyRule('third'));
        $ruleSet->add($this->createDummyRule('fifth'));

        $result = $ruleSet->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEquals($expected, $result->getValues());
    }

    public function testValidationResultSetContainsResultForMissingValueIfARequiredRuleKeyIsNotInTheData(): void
    {
        $data = [
            'first'  => 'string',
            'second' => 'ignored',
            'fourth' => 'also ignored',
            'fifth'  => [1, 2, 3],
        ];

        $expectedValues = [
            'first' => 'string',
            'third' => null,
            'fifth' => [1, 2, 3],
        ];

        $expectedMessages = [
            'third' => 'Missing required value for key third',
        ];

        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRule('first'));
        $ruleSet->add($this->createDummyRule('third', required: true));
        $ruleSet->add($this->createDummyRule('fifth'));

        $result = $ruleSet->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertEquals($expectedValues, $result->getValues());
        $this->assertEquals($expectedMessages, $result->getMessages());
    }

    public function testValidationResultSetContainsResultForValidDefaultValueIfAnOptionalRuleKeyIsNotInTheData(): void
    {
        $data = [
            'first'  => 'string',
            'second' => 'ignored',
            'fourth' => 'also ignored',
            'fifth'  => [1, 2, 3],
        ];

        $expected = [
            'first' => 'string',
            'third' => 1,
            'fifth' => [1, 2, 3],
        ];

        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRule('first'));
        $ruleSet->add($this->createDummyRule('third', default: 1));
        $ruleSet->add($this->createDummyRule('fifth'));

        $result = $ruleSet->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEquals($expected, $result->getValues());
    }

    private function createDummyRule(string $name, mixed $default = null, bool $required = false): Rule
    {
        return new class ($name, $default, $required) implements Rule {
            public function __construct(
                private string $name,
                private mixed $default,
                private bool $required,
            ) {
            }

            public function required(): bool
            {
                return $this->required;
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
                return $this->default;
            }
        };
    }
}
