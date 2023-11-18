<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation\Rule;

use Phly\RuleValidation\Result\Result;
use Phly\RuleValidation\Rule\CallbackRule;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class CallbackRuleTest extends TestCase
{
    public function testCallbackRuleUsesProvidedKey(): CallbackRule
    {
        $key  = 'fieldKey';
        $rule = new CallbackRule(
            $key,
            /** @param non-empty-string $key */
            fn (mixed $value, array $data, string $key) => Result::forValidValue($key, $value),
        );

        $this->assertSame($key, $rule->key());

        return $rule;
    }

    #[Depends('testCallbackRuleUsesProvidedKey')]
    public function testCallbackRuleIsRequiredByDefaultIfNoRequiredFlagProvided(CallbackRule $rule): void
    {
        $this->assertTrue($rule->required());
    }

    #[Depends('testCallbackRuleUsesProvidedKey')]
    public function testCallbackRuleHasNullDefaultIfNoDefaultProvided(CallbackRule $rule): void
    {
        $this->assertNull($rule->default()->value());
    }

    public function testCallbackRuleAcceptsDefaultValueViaConstructor(): void
    {
        $key     = 'fieldKey';
        $default = 'string';
        $rule    = new CallbackRule(
            $key,
            fn (mixed $value) => Result::forValidValue($key, $value),
            default: Result::forValidValue($key, $default),
        );

        $this->assertSame($default, $rule->default()->value());
    }

    public function testCallbackRuleAcceptsRequiredFlagViaConstructor(): void
    {
        $key      = 'fieldKey';
        $required = false;
        $rule     = new CallbackRule(
            $key,
            fn (mixed $value) => Result::forValidValue($key, $value),
            required: $required,
        );

        $this->assertSame($required, $rule->required());
    }

    public function testCallbackRuleUsesCallbackForValidation(): void
    {
        $key         = 'fieldKey';
        $resultValue = 'string';
        $result      = Result::forValidValue($key, $resultValue);
        /** @psalm-var callable(mixed, array<non-empty-string, mixed>, non-empty-string): Result $callback */
        $callback = function (mixed $value, array $context, string $key) use ($result): Result {
            return $result;
        };

        $rule   = new CallbackRule($key, $callback);
        $result = $rule->validate('some value', ['fieldKey' => 'some value', 'someOtherKey' => 'string']);

        $this->assertTrue($result->isValid());
        $this->assertSame($resultValue, $result->value());
    }

    #[Depends('testCallbackRuleUsesProvidedKey')]
    public function testMissingReturnsResultByDefault(CallbackRule $rule): void
    {
        $missing = $rule->missing();
        $this->assertInstanceOf(Result::class, $missing);
        $this->assertFalse($missing->isValid());
    }
}
