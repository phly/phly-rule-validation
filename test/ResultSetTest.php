<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation;

use Phly\RuleValidation\Exception\ResultSetFrozenException;
use Phly\RuleValidation\Exception\UnknownResultException;
use Phly\RuleValidation\Result;
use Phly\RuleValidation\ResultSet;
use PHPUnit\Framework\TestCase;

class ResultSetTest extends TestCase
{
    public function testIsValidReturnsTrueWhenAllResultsAreValid(): void
    {
        $result1   = Result::forValidValue('first', 1);
        $result2   = Result::forValidValue('second', 2);
        $result3   = Result::forValidValue('third', 3);
        $result4   = Result::forValidValue('fourth', 4);
        $resultSet = new ResultSet();

        $resultSet->add($result1);
        $resultSet->add($result2);
        $resultSet->add($result3);
        $resultSet->add($result4);

        $this->assertTrue($resultSet->isValid());
    }

    public function testIsValidReturnsFalseWhenAnyResultIsInvalid(): void
    {
        $result1   = Result::forValidValue('first', 1);
        $result2   = Result::forValidValue('second', 2);
        $result3   = Result::forInvalidValue('third', 3, 'not a valid value');
        $result4   = Result::forValidValue('fourth', 4);
        $resultSet = new ResultSet();

        $resultSet->add($result1);
        $resultSet->add($result2);
        $resultSet->add($result3);
        $resultSet->add($result4);

        $this->assertFalse($resultSet->isValid());
    }

    public function testGetMessagesReturnsMapOfResultKeyToMessageForInvalidResults(): void
    {
        $result1   = Result::forValidValue('first', 1);
        $result2   = Result::forValidValue('second', 2);
        $result3   = Result::forInvalidValue('third', 3, 'not a valid value');
        $result4   = Result::forValidValue('fourth', 4);
        $resultSet = new ResultSet($result1, $result2, $result3, $result4);

        $this->assertFalse($resultSet->isValid(), 'Expected validation to fail, but it did not');

        $messages = $resultSet->getMessages();
        $this->assertEquals(['third' => 'not a valid value'], $messages);
    }

    public function testGetValuesReturnsMapOfResultKeyToValuesForAllResults(): void
    {
        $result1   = Result::forValidValue('first', 1);
        $result2   = Result::forValidValue('second', 2);
        $result3   = Result::forInvalidValue('third', 3, 'not a valid value');
        $result4   = Result::forValidValue('fourth', 4);
        $resultSet = new ResultSet($result1, $result2, $result3, $result4);

        $expected = [
            'first'  => 1,
            'second' => 2,
            'third'  => 3,
            'fourth' => 4,
        ];

        $this->assertEquals($expected, $resultSet->getValues());
    }

    public function testUsingAddSetsOffsetKeyToResultKey(): void
    {
        $result    = Result::forValidValue('first', 1);
        $resultSet = new ResultSet();
        $resultSet->add($result);

        $this->assertSame($result, $resultSet->getResultForKey('first'));
    }

    public function testCallingGetResultForKeyRaisesUnknownResultExceptionIfKeyUnrecognized(): void
    {
        $resultSet = new ResultSet();

        $this->expectException(UnknownResultException::class);
        $resultSet->getResultForKey('first');
    }

    public function testAccessingResultByOffsetRaisesUnknownResultExceptionIfKeyUnrecognized(): void
    {
        $resultSet = new ResultSet();

        $this->expectException(UnknownResultException::class);
        $resultSet->getResultForKey('first');
    }

    public function testAttemptingToAddAResultToAFrozenResultSetRaisesException(): void
    {
        $resultSet = new ResultSet();
        $resultSet->freeze();

        $this->expectException(ResultSetFrozenException::class);
        $resultSet->add(Result::forValidValue('flag', true));
    }
}
