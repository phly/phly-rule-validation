<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use InvalidArgumentException;
use Ramsey\Collection\AbstractCollection;

use function get_debug_type;
use function is_string;
use function sprintf;

/**
 * @extends AbstractCollection<Result>
 */
final class ResultSet extends AbstractCollection
{
    public function getType(): string
    {
        return Result::class;
    }

    public function add(mixed $element): bool
    {
        if (! $element instanceof Result) {
            throw new InvalidArgumentException(sprintf(
                'Expected %s; received %s',
                Result::class,
                get_debug_type($element)
            ));
        }

        $this[$element->key] = $element;
        return true;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (! $value instanceof Result) {
            throw new InvalidArgumentException(sprintf(
                'Expected %s; received %s',
                Result::class,
                get_debug_type($value)
            ));
        }

        if (is_string($offset) && $offset !== $value->key) {
            throw Exception\ResultKeyMismatchException::forKeys($value->key, $offset);
        }

        parent::offsetSet($offset, $value);
    }

    /** @throws Exception\UnknownResultException */
    public function offsetGet(mixed $offset): mixed
    {
        if (! $this->offsetExists($offset)) {
            throw Exception\UnknownResultException::forKey((string) $offset);
        }

        return parent::offsetGet($offset);
    }

    /** @throws Exception\UnknownResultException */
    public function getResultForKey(string $key): Result
    {
        foreach ($this as $result) {
            if ($result->key === $key) {
                return $result;
            }
        }

        throw Exception\UnknownResultException::forKey($key);
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
