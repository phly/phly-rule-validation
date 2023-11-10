<?php

declare(strict_types=1);

namespace Phly\RuleValidation;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function array_key_exists;
use function array_reduce;

/** @template-implements IteratorAggregate<Result> */
final class ResultSet implements IteratorAggregate
{
    private bool $frozen = false;

    /** @var array<string, Result> */
    private $results = [];

    public function __construct(Result ...$results)
    {
        foreach ($results as $result) {
            $this->add($result);
        }
    }

    public function __get(string $key): ?Result
    {
        return array_key_exists($key, $this->results) ? $this->results[$key] : null;
    }

    /** @return Traversable<Result> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->results);
    }

    public function add(Result $result): void
    {
        if ($this->frozen) {
            throw new Exception\ResultSetFrozenException();
        }

        $key = $result->key;
        $this->guardForDuplicateKey($key);
        $this->results[$key] = $result;
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
        return array_reduce($this->results, function (bool $isValid, Result $result): bool {
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
    public function getValues(): array
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
    public function freeze(): void
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
