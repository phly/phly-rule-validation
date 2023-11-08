<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use Ramsey\Collection\AbstractCollection;

/**
 * @extends AbstractCollection<Result>
 */
final class ResultSet extends AbstractCollection
{
    public function getType(): string
    {
        return Result::class;
    }

    public function isValid(): bool
    {
        return $this->reduce(function (bool $isValid, Result $result): bool {
            if ($isValid === false) {
                return false;
            }
            return $result->isValid;
        }, true);
    }

    /** @return array<array-key, null|string> */
    public function getMessages(): array
    {
        $messages = [];
        foreach ($this as $key => $result) {
            /** @var string $key */
            if ($result->isValid) {
                continue;
            }
            $messages[$key] = $result->message;
        }
        return $messages;
    }

    /** @return array<string, mixed> */
    public function getValues(): array
    {
        $values = [];
        foreach ($this as $key => $result) {
            /**
             * @var string $key
             * @psalm-suppress MixedAssignment
             */
            $values[$key] = $result->value;
        }
        return $values;
    }
}
