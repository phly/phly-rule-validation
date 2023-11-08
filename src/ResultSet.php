<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use Ramsey\Collection\AbstractCollection;

/**
 * @extends AbstractCollection<Result>
 */
final class ResultSet extends AbstractCollection
{
    private ?bool $isValid = null;

    public function getType(): string
    {
        return Result::class;
    }

    public function isValid(): bool
    {
        if (null !== $this->isValid) {
            return $this->isValid;
        }

        $this->isValid = $this->reduce(function (bool $isValid, Result $result): bool {
            if ($isValid === false) {
                return false;
            }
            return $result->isValid;
        }, true);

        return $this->isValid;
    }

    /** @return array<array-key, null|string> */
    public function getMessages(): array
    {
        $messages = [];
        foreach ($this as $key => $result) {
            if ($result->isValid) {
                continue;
            }
            /** @var string $key */
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
