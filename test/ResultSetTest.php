<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation;

use Phly\RuleValidation\Result;
use Phly\RuleValidation\ResultSet;
use PHPUnit\Framework\TestCase;

class ResultSetTest extends TestCase
{
    public function testResultSetCollectsResults(): void
    {
        $resultSet = new ResultSet();
        $this->assertSame(Result::class, $resultSet->getType());
    }

    public function testIsValidReturnsTrueWhenAllResultsAreValid(): void
    {
        $result1   = Result::forValidValue(1);
        $result2   = Result::forValidValue(2);
        $result3   = Result::forValidValue(3);
        $result4   = Result::forValidValue(4);
        $resultSet = new ResultSet();

        $resultSet->add($result1);
        $resultSet->add($result2);
        $resultSet->add($result3);
        $resultSet->add($result4);

        $this->assertTrue($resultSet->isValid());
    }

    public function testIsValidReturnsFalseWhenAnyResultIsInvalid(): void
    {
        $result1   = Result::forValidValue(1);
        $result2   = Result::forValidValue(2);
        $result3   = Result::forInvalidValue(3, 'not a valid value');
        $result4   = Result::forValidValue(4);
        $resultSet = new ResultSet();

        $resultSet->add($result1);
        $resultSet->add($result2);
        $resultSet->add($result3);
        $resultSet->add($result4);

        $this->assertFalse($resultSet->isValid());
    }

    public function testGetMessagesReturnsMapOfResultKeyToMessageForInvalidResults(): void
    {
        $result1   = Result::forValidValue(1);
        $result2   = Result::forValidValue(2);
        $result3   = Result::forInvalidValue(3, 'not a valid value');
        $result4   = Result::forValidValue(4);
        $resultSet = new ResultSet();

        $resultSet['first']  = $result1;
        $resultSet['second'] = $result2;
        $resultSet['third']  = $result3;
        $resultSet['fourth'] = $result4;

        $this->assertFalse($resultSet->isValid(), 'Expected validation to fail, but it did not');

        $messages = $resultSet->getMessages();
        $this->assertEquals(['third' => 'not a valid value'], $messages);
    }

    public function getGetValuesReturnsMapOfResultKeyToValuesForAllResults(): void
    {
        $result1   = Result::forValidValue(1);
        $result2   = Result::forValidValue(2);
        $result3   = Result::forInvalidValue(3, 'not a valid value');
        $result4   = Result::forValidValue(4);
        $resultSet = new ResultSet();

        $resultSet['first']  = $result1;
        $resultSet['second'] = $result2;
        $resultSet['third']  = $result3;
        $resultSet['fourth'] = $result4;

        $expected = [
            'first'  => 1,
            'second' => 2,
            'third'  => 3,
            'fourth' => 4,
        ];

        $this->assertEquals($expected, $resultSet->getValues());
    }
}
