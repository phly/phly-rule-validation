<?php

declare(strict_types=1);

namespace PhlyTest\RuleValidation\TestAsset;

use Phly\RuleValidation\Result;
use Phly\RuleValidation\ResultSet;

/**
 * @property-read Result<string> $first
 * @property-read Result<string> $second
 * @property-read Result<int> $third
 * @property-read Result<null|int> $fourth
 */
class CustomResultSet extends ResultSet
{
}
