<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

/** @template T of ResultSet */
interface RuleSetValidator
{
    /**
     * @param array<non-empty-string, mixed> $data
     * @return T
     */
    public function validate(array $data): ResultSet;

    /**
     * @param array<non-empty-string, mixed> $valueMap
     * @return T
     */
    public function createValidResultSet(array $valueMap = []): ResultSet;

    /** @param non-empty-string $key */
    public function getRule(string $key): ?Rule;
}
