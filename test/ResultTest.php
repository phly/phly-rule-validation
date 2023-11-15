<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation;

use Generator;
use Phly\RuleValidation\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    /** @psalm-return Generator<string, array<array-key, mixed>> */
    public static function valueProvider(): Generator
    {
        yield 'null'    => [null];
        yield 'true'    => [true];
        yield 'false'   => [false];
        yield 'zero'    => [0];
        yield 'integer' => [1];
        yield 'float'   => [1.1];
        yield 'string'  => ['some string'];
        yield 'list'    => [[null, 0, 1.1, true, false, 'string']];
        yield 'map'     => [
            [
                'null'   => null,
                'zero'   => 0,
                'float'  => 1.1,
                'true'   => true,
                'false'  => false,
                'string' => 'string',
            ],
        ];
        yield 'object'  => [
            new class () {
            },
        ];
    }

    #[DataProvider('valueProvider')]
    public function testForValidValueMarksResultValidSetsValueAndHasNullMessage(mixed $value): void
    {
        $result = Result::forValidValue('key', $value);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isValid());
        $this->assertSame($value, $result->value());
        $this->assertNull($result->message());
    }

    #[DataProvider('valueProvider')]
    public function testForInvalidValueMarksResultInvalidAndSetsValueAndMessage(mixed $value): void
    {
        $message = 'this is the message';
        $result  = Result::forInvalidValue('key', $value, $message);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isValid());
        $this->assertSame($value, $result->value());
        $this->assertSame($message, $result->message());
    }

    public function testForMissingValueMarksResultInvalidAndSetsMessage(): void
    {
        $message = 'this is the message';
        $result  = Result::forMissingValue('first', $message);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertFalse($result->isValid());
        $this->assertNull($result->value());
        $this->assertSame($message, $result->message());
    }
}
