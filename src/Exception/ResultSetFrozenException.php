<?php

declare(strict_types=1);

namespace Phly\RuleValidation\Exception;

use Phly\RuleValidation\ResultSet;
use RuntimeException;

use function sprintf;

class ResultSetFrozenException extends RuntimeException implements ValidationException
{
    public function __construct()
    {
        parent::__construct(sprintf('Cannot add results to a completed %s', ResultSet::class), 500);
    }
}
