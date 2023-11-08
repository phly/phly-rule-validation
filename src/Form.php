<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use function array_key_exists;

final class Form
{
    public RuleSet $rules;

    public function __construct()
    {
        $this->rules = new RuleSet();
    }

    public function validate(array $data): ResultSet
    {
        $resultSet = new ResultSet();

        foreach ($this->rules as $rule) {
            $key = $rule->for();
            if (array_key_exists($key, $data)) {
                $resultSet[$key] = $rule->validate($data[$key], $data);
                continue;
            }

            if ($rule->required()) {
                $resultSet[$key] = Result::forMissingValue('Missing required value for key ' . $key);
                continue;
            }

            $resultSet[$key] = Result::forValidValue($rule->default());
        }

        return $resultSet;
    }
}
