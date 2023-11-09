<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation\Rule;

use Generator;
use Phly\RuleValidation\Rule\BooleanRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BooleanRuleTest extends TestCase
{
    /** @return Generator<string, array<array-key, mixed>> */
    public static function invalidValueProvider(): Generator
    {
        yield 'null'   => [null];
        yield 'zero'   => [0];
        yield 'int'    => [1];
        yield 'string' => ['string'];
        yield 'list'   => [[null, 0, 1, 'string']];
        yield 'map'    => [['null' => null, 'zero' => 0, 'int' => 1, 'string' => 'string']];
        yield 'object' => [
            new class {
            },
        ];
    }

    #[DataProvider('invalidValueProvider')]
    public function testReturnsInvalidValueResultForNonBooleanValue(mixed $value): void
    {
        $rule   = new BooleanRule(key: 'flag');
        $result = $rule->validate($value, []);
        $this->assertFalse($result->isValid);
    }

    public function testReturnsValidResultForTrueValue(): void
    {
        $rule   = new BooleanRule(key: 'flag');
        $result = $rule->validate(true, []);
        $this->assertTrue($result->isValid);
        $this->assertTrue($result->value);
    }

    public function testReturnsValidResultForFalseValue(): void
    {
        $rule   = new BooleanRule(key: 'flag');
        $result = $rule->validate(false, []);
        $this->assertTrue($result->isValid);
        $this->assertFalse($result->value);
    }
}
