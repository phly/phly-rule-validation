<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation;

use Phly\RuleValidation\Form;
use Phly\RuleValidation\Result;
use Phly\RuleValidation\Rule;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testReturnsAnEmptyResultSetWhenNoRulesPresent(): void
    {
        $form   = new Form();
        $result = $form->validate(['some' => 'data']);
        $this->assertCount(0, $result);
    }

    public function testReturnsAPopulatedResultSetWithAKeyMatchingEachRule(): void
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

        $form = new Form();
        $form->rules->add($this->createDummyRule('first'));
        $form->rules->add($this->createDummyRule('third'));
        $form->rules->add($this->createDummyRule('fifth'));

        $result = $form->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEquals($expected, $result->getValues());
    }

    public function testResultSetContainsResultForMissingValueIfARequiredRuleKeyIsNotInTheData(): void
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

        $form = new Form();
        $form->rules->add($this->createDummyRule('first'));
        $form->rules->add($this->createDummyRule('third', required: true));
        $form->rules->add($this->createDummyRule('fifth'));

        $result = $form->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertEquals($expectedValues, $result->getValues());
        $this->assertEquals($expectedMessages, $result->getMessages());
    }

    public function testResultSetContainsResultForValidDefaultValueIfAnOptionalRuleKeyIsNotInTheData(): void
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

        $form = new Form();
        $form->rules->add($this->createDummyRule('first'));
        $form->rules->add($this->createDummyRule('third', default: 1));
        $form->rules->add($this->createDummyRule('fifth'));

        $result = $form->validate($data);

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
                return $this->default;
            }
        };
    }
}
