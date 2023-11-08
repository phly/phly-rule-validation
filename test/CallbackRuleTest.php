<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation;

use Phly\RuleValidation\CallbackRule;
use Phly\RuleValidation\Result;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class CallbackRuleTest extends TestCase
{
    public function testCallbackRuleUsesProvidedKeyAsReturnValueFor(): CallbackRule
    {
        $key  = 'fieldKey';
        $rule = new CallbackRule(
            $key,
            fn (mixed $value) => Result::forValidValue($value),
        );

        $this->assertSame($key, $rule->for());

        return $rule;
    }

    #[Depends('testCallbackRuleUsesProvidedKeyAsReturnValueFor')]
    public function testCallbackRuleIsRequiredByDefaultIfNoRequiredFlagProvided(CallbackRule $rule): void
    {
        $this->assertTrue($rule->required());
    }

    #[Depends('testCallbackRuleUsesProvidedKeyAsReturnValueFor')]
    public function testCallbackRuleHasNullDefaultIfNoDefaultProvided(CallbackRule $rule): void
    {
        $this->assertNull($rule->default());
    }

    public function testCallbackRuleAcceptsDefaultValueViaConstructor(): void
    {
        $key     = 'fieldKey';
        $default = 'string';
        $rule    = new CallbackRule(
            $key,
            fn (mixed $value) => Result::forValidValue($value),
            default: $default,
        );

        $this->assertSame($default, $rule->default());
    }

    public function testCallbackRuleAcceptsRequiredFlagViaConstructor(): void
    {
        $key      = 'fieldKey';
        $required = false;
        $rule     = new CallbackRule(
            $key,
            fn (mixed $value) => Result::forValidValue($value),
            required: $required,
        );

        $this->assertSame($required, $rule->required());
    }

    public function testCallbackRuleUsesCallbackForValidation(): void
    {
        $key         = 'fieldKey';
        $resultValue = 'string';
        $result      = Result::forValidValue($resultValue);
        $callback    = function (mixed $value, array $context) use ($result): Result {
            return $result;
        };

        $rule   = new CallbackRule($key, $callback);
        $result = $rule->validate('some value', ['fieldKey' => 'some value', 'someOtherKey' => 'string']);

        $this->assertTrue($result->isValid);
        $this->assertSame($resultValue, $result->value);
    }
}
