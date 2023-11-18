<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation\Result;

use OutOfRangeException;
use Phly\RuleValidation\Result\NestedResult;
use Phly\RuleValidation\Result\Result;
use Phly\RuleValidation\ResultSet;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class NestedResultTest extends TestCase
{
    public function testIssetReturnsFalseWhenComposedValueIsAResultSetButDoesNotContainRequestedName(): void
    {
        $resultSet = new ResultSet();
        $author    = NestedResult::forValidValue('author', $resultSet);
        $this->assertFalse(isset($author->name));
    }

    public function testIssetReturnsTrueWhenComposedValueIsAResultSetAndContainsRequestedName(): void
    {
        $resultSet = new ResultSet(Result::forValidValue('name', 'Dirk Gently'));
        $author    = NestedResult::forValidValue('author', $resultSet);
        /** @psalm-suppress RedundantCondition */
        $this->assertTrue(isset($author->name));
    }

    public function testGetRaisesExceptionWhenComposedValueIsAResultSetButDoesNotContainRequestedName(): void
    {
        $resultSet = new ResultSet();
        /** @var NestedResult<ResultSet> $author */
        $author = NestedResult::forValidValue('author', $resultSet);

        $this->expectException(OutOfRangeException::class);
        $author->name;
    }

    public function testGetRaisesExceptionWhenComposedValueIsAResultSetButValueAssociatedWithNameIsNotAResult(): void
    {
        $resultSet = new class extends ResultSet {
            public string $name = 'Dirk Gently';
        };
        /** @var NestedResult<ResultSet> $author */
        $author = NestedResult::forValidValue('author', $resultSet);

        $this->expectException(UnexpectedValueException::class);
        $author->name;
    }

    public function testGetReturnsResultAssociatedWithNameInComposedResultSet(): void
    {
        /** @var Result<string> $result */
        $result    = Result::forValidValue('name', 'Dirk Gently');
        $resultSet = new ResultSet($result);
        /** @var NestedResult<ResultSet> $author */
        $author = NestedResult::forValidValue('author', $resultSet);

        $this->assertSame($result, $author->name);
    }
}
