<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation\RuleSet;

use Phly\RuleValidation\Exception\DuplicateRuleKeyException;
use Phly\RuleValidation\Exception\RequiredRuleWithNoDefaultValueException;
use Phly\RuleValidation\Result\CreateMissingValueResult;
use Phly\RuleValidation\Result\Result;
use Phly\RuleValidation\ResultSet;
use Phly\RuleValidation\Rule;
use Phly\RuleValidation\RuleSet\RuleSet;
use Phly\RuleValidation\RuleSet\RuleSetOptions;
use Phly\RuleValidation\ValidationResult;
use PhlyTest\RuleValidation\TestAsset\CustomResultSet;
use PHPUnit\Framework\TestCase;

use function array_key_exists;

class RuleSetTest extends TestCase
{
    public function testInstantiatingRuleSetWithRulesForSameKeyRaisesDuplicateKeyException(): void
    {
        $this->expectException(DuplicateRuleKeyException::class);
        $this->expectExceptionMessage('Duplicate validation rule detected for key "first"');
        RuleSet::createWithRules(
            $this->createDummyRule('first'),
            $this->createDummyRule('first'),
        );
    }

    public function testGetRuleReturnsRuleMatchingKey(): void
    {
        $rule1 = $this->createDummyRule('first');
        $rule2 = $this->createDummyRule('second');
        $rule3 = $this->createDummyRule('third');
        $rule4 = $this->createDummyRule('fourth');

        $ruleSet = RuleSet::createWithRules($rule2, $rule3, $rule1, $rule4);

        $this->assertSame($rule1, $ruleSet->getRule('first'));
        $this->assertSame($rule2, $ruleSet->getRule('second'));
        $this->assertSame($rule3, $ruleSet->getRule('third'));
        $this->assertSame($rule4, $ruleSet->getRule('fourth'));
    }

    public function testGetRuleReturnsNullIfNoRuleMatchingKeyFound(): void
    {
        $rule1 = $this->createDummyRule('first');
        $rule2 = $this->createDummyRule('second');

        $ruleSet = RuleSet::createWithRules($rule1, $rule2);

        $this->assertNull($ruleSet->getRule('fourth'));
    }

    public function testValidationReturnsAnEmptyResultSetWhenNoRulesPresent(): void
    {
        $ruleSet = new RuleSet(new RuleSetOptions());
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

        $ruleSet = RuleSet::createWithRules(
            $this->createDummyRule('first'),
            $this->createDummyRule('third'),
            $this->createDummyRule('fifth'),
        );

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
            'third' => 'Missing required value',
        ];

        $ruleSet = RuleSet::createWithRules(
            $this->createDummyRule('first'),
            $this->createDummyRule('third', required: true),
            $this->createDummyRule('fifth'),
        );

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

        $ruleSet = RuleSet::createWithRules(
            $this->createDummyRule('first'),
            $this->createDummyRule('third', default: 1),
            $this->createDummyRule('fifth'),
        );

        $result = $ruleSet->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEquals($expected, $result->getValues());
    }

    public function testValidationAllowsOverridingMissingValueMessageViaOptions(): void
    {
        $options = new RuleSetOptions();
        $options->setMissingValueResultFactory(
            new class implements CreateMissingValueResult {
                private const MISSING_KEY_MAP = [
                    'title' => 'Please provide a title',
                    // ...
                ];

                /** @psalm-param non-empty-string $key */
                public function __invoke(string $key): ValidationResult
                {
                    if (array_key_exists($key, self::MISSING_KEY_MAP)) {
                        return Result::forMissingValue($key, self::MISSING_KEY_MAP[$key]);
                    }

                    return Result::forMissingValue($key);
                }
            }
        );
        $options->addRule($this->createDummyRule('title', required: true));

        $ruleSet = new RuleSet($options);
        $result  = $ruleSet->validate([]);

        $this->assertFalse($result->isValid());
        $this->assertSame('Please provide a title', $result->getResult('title')->message());
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
        $ruleSet = RuleSet::createWithRules(
            $this->createDummyRule('first'),
            $this->createDummyRule('second', required: true, default: 'string'),
            $this->createDummyRule('third', default: 1),
            $this->createDummyRule('fourth', required: true),
        );

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
        $ruleSet = RuleSet::createWithRules($this->createDummyRule('first'));
        $form    = $ruleSet->createValidResultSet(['first' => 'initial value', 'fourth' => 42]);

        $this->assertInstanceOf(ResultSet::class, $form);
        $this->assertTrue(isset($form->first));
        $this->assertSame('initial value', $form->first->value());
        $this->assertFalse(isset($form->fourth));
    }

    public function testCreateValidResultSetUsesNullForOptionalRulesWithNoDefaultValueAndNoValueInValueMap(): void
    {
        $ruleSet = RuleSet::createWithRules($this->createDummyRule('first'));
        $form    = $ruleSet->createValidResultSet();

        $this->assertInstanceOf(ResultSet::class, $form);
        $this->assertTrue(isset($form->first));
        $this->assertNull($form->first->value());
    }

    public function testCreateValidResultSetRaisesExceptionForRequiredRulesWithNoDefaultAndNoValueInValueMap(): void
    {
        $ruleSet = RuleSet::createWithRules($this->createDummyRule('first', required: true));

        $this->expectException(RequiredRuleWithNoDefaultValueException::class);
        $ruleSet->createValidResultSet();
    }

    public function testCreateValidResultSetUsesProvidedResultSetClassNameWhenPresent(): void
    {
        $options = new RuleSetOptions();
        $options->setResultSetClass(CustomResultSet::class);
        $options->addRule($this->createDummyRule('first'));
        $options->addRule($this->createDummyRule('second', required: true, default: 'string'));
        $options->addRule($this->createDummyRule('third', default: 1));
        $options->addRule($this->createDummyRule('fourth', required: true));

        /** @var RuleSet<CustomResultSet> $ruleSet */
        $ruleSet = new RuleSet($options);
        $form    = $ruleSet->createValidResultSet(['first' => 'initial value', 'fourth' => 42]);

        $this->assertInstanceOf(CustomResultSet::class, $form);
        $this->assertSame('initial value', $form->first->value());
        $this->assertSame('string', $form->second->value());
        $this->assertSame(1, $form->third->value());
        $this->assertSame(42, $form->fourth->value());
    }

    public function testValidateReturnsAlternateResultClassNameProvidedViaOptions(): void
    {
        $options = new RuleSetOptions();
        $options->setResultSetClass(CustomResultSet::class);
        $options->addRule($this->createDummyRule('first'));
        $options->addRule($this->createDummyRule('second', required: true, default: 'string'));
        $options->addRule($this->createDummyRule('third', default: 1));
        $options->addRule($this->createDummyRule('fourth', required: true));

        /** @var RuleSet<CustomResultSet> $ruleSet */
        $ruleSet = new RuleSet($options);
        $result  = $ruleSet->validate(['some' => 'data']);

        $this->assertInstanceOf(CustomResultSet::class, $result);
    }

    public function testIssetOnExistingRuleKeyReturnsTrue(): void
    {
        $ruleSet = RuleSet::createWithRules($this->createDummyRule('first'));
        $this->assertTrue(isset($ruleSet->first));
    }

    public function testIssetOnNonExistentRuleKeyReturnsFalse(): void
    {
        $ruleSet = RuleSet::createWithRules();
        $this->assertFalse(isset($ruleSet->first));
    }

    public function testCanAccessRuleViaPropertyAccess(): void
    {
        $rule    = $this->createDummyRule('first');
        $ruleSet = RuleSet::createWithRules($rule);

        $this->assertSame($rule, $ruleSet->first);
    }

    public function testPropertyAccessOfNonExistentRuleReturnsNull(): void
    {
        $ruleSet = RuleSet::createWithRules();
        $this->assertNull($ruleSet->first);
    }
}
