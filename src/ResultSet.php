<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function array_key_exists;
use function array_reduce;

/**
 * Validation result set
 *
 * The main reason to extend this class is to provide a list of expected Result
 * mappings, with the expected Result value types:
 *
 * <code>
 * /**
 *  * @property-read Result<string> $title
 *  * @property-read Result<string> $description
 *  * @property-read Result<DateTimeImmutable> $creationDate
 *  * /
 * class CustomResultSet extends ResultSet
 * {
 * }
 * </code>
 *
 * Doing so will give you IDE type hints, as well as allow Psalm and/or PHPStan
 * to infer about composed Result instances correctly.
 *
 * @template-implements IteratorAggregate<Result>
 */
class ResultSet implements IteratorAggregate
{
    private bool $frozen = false;

    /** @var array<string, Result> */
    private $results = [];

    final public function __construct(Result ...$results)
    {
        foreach ($results as $result) {
            $this->add($result);
        }
    }

    final public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->results);
    }

    final public function __get(string $key): ?Result
    {
        return array_key_exists($key, $this->results) ? $this->results[$key] : null;
    }

    /** @return Traversable<Result> */
    final public function getIterator(): Traversable
    {
        return new ArrayIterator($this->results);
    }

    final public function add(Result $result): void
    {
        if ($this->frozen) {
            throw new Exception\ResultSetFrozenException();
        }

        $key = $result->key;
        $this->guardForDuplicateKey($key);
        $this->results[$key] = $result;
    }

    /** @throws Exception\UnknownResultException */
    final public function getResultForKey(string $key): Result
    {
        foreach ($this as $result) {
            if ($result->key === $key) {
                return $result;
            }
        }

        throw Exception\UnknownResultException::forKey($key);
    }

    final public function isValid(): bool
    {
        return array_reduce($this->results, function (bool $isValid, Result $result): bool {
            if ($isValid === false) {
                return false;
            }
            return $result->isValid;
        }, true);
    }

    /** @return array<array-key, null|string> */
    final public function getMessages(): array
    {
        $messages = [];
        foreach ($this->results as $key => $result) {
            /** @var string $key */
            if ($result->isValid) {
                continue;
            }
            $messages[$key] = $result->message;
        }
        return $messages;
    }

    /** @return array<string, mixed> */
    final public function getValues(): array
    {
        $values = [];
        foreach ($this->results as $key => $result) {
            /**
             * @var string $key
             * @psalm-suppress MixedAssignment
             */
            $values[$key] = $result->value;
        }
        return $values;
    }

    /**
     * Freeze the result set
     *
     * Once called, no more results may be added to the result set.
     */
    final public function freeze(): void
    {
        $this->frozen = true;
    }

    /** @throws Exception\DuplicateResultKeyException */
    private function guardForDuplicateKey(string $key): void
    {
        if (array_key_exists($key, $this->results)) {
            throw Exception\DuplicateResultKeyException::forKey($key);
        }
    }
}
