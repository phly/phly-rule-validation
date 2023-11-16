<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation\RuleSet;

use Phly\RuleValidation\Exception\DuplicateRuleKeyException;
use Phly\RuleValidation\Exception\RequiredRuleWithNoDefaultValueException;
use Phly\RuleValidation\Exception\ResultSetFrozenException;
use Phly\RuleValidation\Result;
use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\RuleSet\RuleSet;
use PhlyTest\RuleValidation\TestAsset\CustomResultSet;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

use function array_key_exists;

class RuleSetTest extends TestCase
{
    public function testCallingAddWithARuleWithANameUsedByAnotherRuleInTheRuleSetRaisesDuplicateKeyException(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRule('first'));

        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        $ruleSet->add($this->createDummyRule('first'));
    }

    public function testInstantiatingRuleSetWithRulesForSameKeyRaisesDuplicateKeyException(): void
    {
        $rule1 = $this->createDummyRule('first');
        $rule2 = $this->createDummyRule('first');

        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        new RuleSet(...[$rule1, $rule2]);
    }

    public function testGetRuleForKeyReturnsRuleMatchingKey(): void
    {
        $rule1 = $this->createDummyRule('first');
        $rule2 = $this->createDummyRule('second');
        $rule3 = $this->createDummyRule('third');
        $rule4 = $this->createDummyRule('fourth');

        $ruleSet = new RuleSet($rule2, $rule3, $rule1, $rule4);

        $this->assertSame($rule1, $ruleSet->getRuleForKey('first'));
        $this->assertSame($rule2, $ruleSet->getRuleForKey('second'));
        $this->assertSame($rule3, $ruleSet->getRuleForKey('third'));
        $this->assertSame($rule4, $ruleSet->getRuleForKey('fourth'));
    }

    public function testGetRuleForKeyReturnsNullIfNoRuleMatchingKeyFound(): void
    {
        $rule1 = $this->createDummyRule('first');
        $rule2 = $this->createDummyRule('second');

        $ruleSet = new RuleSet($rule1, $rule2);

        $this->assertNull($ruleSet->getRuleForKey('fourth'));
    }

    public function testValidationReturnsAnEmptyResultSetWhenNoRulesPresent(): void
    {
        $ruleSet = new RuleSet();
        $result  = $ruleSet->validate(['some' => 'data']);
        $this->assertCount(0, $result);
    }

    public function testValidationReturnsAPopulatedResultSetWithAKeyMatchingEachRule(): ResultSet
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

        return $result;
    }

    #[Depends('testValidationReturnsAPopulatedResultSetWithAKeyMatchingEachRule')]
    public function testResultSetOfValidationIsFrozen(ResultSet $resultSet): void
    {
        $this->expectException(ResultSetFrozenException::class);
        $resultSet->add(Result::forValidValue('anotherInput', 'string'));
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
            'third' => 'Missing required value',
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

    public function testValidationAllowsOverridingMissingValueMessageViaExtension(): void
    {
        $ruleSet = new /** @template-extends RuleSet<ResultSet> */ class extends RuleSet {
            private const MISSING_KEY_MAP = [
                'title' => 'Please provide a title',
                // ...
            ];

            /** @psalm-param non-empty-string $key */
            public function createMissingValueResultForKey(string $key): Result
            {
                if (array_key_exists($key, self::MISSING_KEY_MAP)) {
                    return Result::forMissingValue($key, self::MISSING_KEY_MAP[$key]);
                }

                return Result::forMissingValue($key);
            }
        };

        $ruleSet->add($this->createDummyRule('title', required: true));
        $result = $ruleSet->validate([]);

        $this->assertFalse($result->isValid());
        $this->assertSame('Please provide a title', $result->getResultForKey('title')->message());
    }

    /** @param non-empty-string $name */
    private function createDummyRule(string $name, mixed $default = null, bool $required = false): Rule
    {
        return new class ($name, $default, $required) implements Rule {
            public function __construct(
                /** @var non-empty-string */
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
                return Result::forValidValue($this->name, $value);
            }

            public function default(): mixed
            {
                return $this->default;
            }
        };
    }

    public function testCreateValidResultSetCreatesValidResultSetUsingMap(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRule('first'));
        $ruleSet->add($this->createDummyRule('second', required: true, default: 'string'));
        $ruleSet->add($this->createDummyRule('third', default: 1));
        $ruleSet->add($this->createDummyRule('fourth', required: true));

        $form = $ruleSet->createValidResultSet(['first' => 'initial value', 'fourth' => 42]);

        $this->assertInstanceOf(ResultSet::class, $form);
        $this->assertTrue(isset($form->first));
        $this->assertSame('initial value', $form->first->value());
        $this->assertTrue(isset($form->second));
        $this->assertSame('string', $form->second->value());
        $this->assertTrue(isset($form->third));
        $this->assertSame(1, $form->third->value());
        $this->assertTrue(isset($form->fourth));
        $this->assertSame(42, $form->fourth->value());
    }

    public function testCreateValidResultSetOmitsResultsForKeysNotMatchingAnyRules(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRule('first'));

        $form = $ruleSet->createValidResultSet(['first' => 'initial value', 'fourth' => 42]);

        $this->assertInstanceOf(ResultSet::class, $form);
        $this->assertTrue(isset($form->first));
        $this->assertSame('initial value', $form->first->value());
        $this->assertFalse(isset($form->fourth));
    }

    public function testCreateValidResultSetUsesNullForOptionalRulesWithNoDefaultValueAndNoValueInValueMap(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRule('first'));

        $form = $ruleSet->createValidResultSet();

        $this->assertInstanceOf(ResultSet::class, $form);
        $this->assertTrue(isset($form->first));
        $this->assertNull($form->first->value());
    }

    public function testCreateValidResultSetRaisesExceptionForRequiredRulesWithNoDefaultAndNoValueInValueMap(): void
    {
        $ruleSet = new RuleSet();
        $ruleSet->add($this->createDummyRule('first', required: true));

        $this->expectException(RequiredRuleWithNoDefaultValueException::class);
        $ruleSet->createValidResultSet();
    }

    public function testCreateValidResultSetUsesProvidedResultSetClassNameWhenPresent(): void
    {
        /** @var RuleSet<CustomResultSet> $ruleSet */
        $ruleSet = RuleSet::createWithResultSetClass(CustomResultSet::class);
        $ruleSet->add($this->createDummyRule('first'));
        $ruleSet->add($this->createDummyRule('second', required: true, default: 'string'));
        $ruleSet->add($this->createDummyRule('third', default: 1));
        $ruleSet->add($this->createDummyRule('fourth', required: true));

        $form = $ruleSet->createValidResultSet(['first' => 'initial value', 'fourth' => 42]);

        $this->assertInstanceOf(CustomResultSet::class, $form);
        $this->assertSame('initial value', $form->first->value());
        $this->assertSame('string', $form->second->value());
        $this->assertSame(1, $form->third->value());
        $this->assertSame(42, $form->fourth->value());
    }

    public function testValidateAllowsProvidingAlternateResultClassName(): void
    {
        /** @var RuleSet<CustomResultSet> $ruleSet */
        $ruleSet = RuleSet::createWithResultSetClass(CustomResultSet::class);
        $ruleSet->add($this->createDummyRule('first'));
        $ruleSet->add($this->createDummyRule('second', required: true, default: 'string'));
        $ruleSet->add($this->createDummyRule('third', default: 1));
        $ruleSet->add($this->createDummyRule('fourth', required: true));

        $result = $ruleSet->validate(['some' => 'data']);

        $this->assertInstanceOf(CustomResultSet::class, $result);
    }
}
