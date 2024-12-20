<?php

namespace App\Infrastructure\ValueObject;

/**
 * @template T
 *
 * @implements \IteratorAggregate<int, T>
 */
abstract class Collection implements \Countable, \IteratorAggregate, \JsonSerializable
{
    /** @var array<T> */
    private array $items = [];

    abstract public function getItemClassName(): string;

    public static function empty(): static
    {
        return new static([]);
    }

    /**
     * @param array<T> $items
     */
    public static function fromArray(array $items): static
    {
        return new static($items);
    }

    /**
     * @param array<T> $items
     */
    final private function __construct(array $items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * @param T $item
     */
    public function has($item): bool
    {
        $this->guardItemIsInstanceOfItemClassName($item);

        return \in_array($item, $this->items);
    }

    /**
     * @param T $item
     *
     * @return Collection<T>
     */
    public function add($item): self
    {
        $this->guardItemIsInstanceOfItemClassName($item);
        $this->items[] = $item;

        return $this;
    }

    /**
     * @return Collection<T>
     */
    public function remove(int|string $index): self
    {
        if (array_key_exists($index, $this->items)) {
            unset($this->items[$index]);
        }

        $this->items = array_values($this->items);

        return $this;
    }

    /**
     * @param Collection<T> $collection
     *
     * @return Collection<T>
     */
    public function mergeWith(Collection $collection): self
    {
        foreach ($collection as $item) {
            $this->add($item);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return 0 === $this->count();
    }

    public function count(): int
    {
        return \count($this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return array<T>
     */
    public function toArray(): array
    {
        return \array_values($this->items);
    }

    /**
     * @return T|null
     */
    public function getFirst(): mixed
    {
        if ($this->isEmpty()) {
            return null;
        }

        $items = $this->toArray();
        /** @var T $item */
        $item = reset($items);

        return $item;
    }

    /**
     * @return T|null
     */
    public function getLast(): mixed
    {
        if ($this->isEmpty()) {
            return null;
        }

        $items = $this->toArray();
        /** @var T $item */
        $item = end($items);

        return $item;
    }

    public function reverse(): static
    {
        return static::fromArray(array_reverse($this->items));
    }

    /**
     * @return array<mixed>
     */
    public function map(\Closure $closure): array
    {
        return array_map(fn ($item): mixed => $closure($item), $this->items);
    }

    public function sum(\Closure $closure): int|float
    {
        /** @var array<float|int> $numbers */
        $numbers = $this->map(fn ($item): int|float => $closure($item));

        return array_sum($numbers);
    }

    public function max(\Closure $closure): mixed
    {
        /** @var non-empty-array<float|int> $numbers */
        $numbers = $this->map(fn ($item): int|float => $closure($item));

        return max($numbers);
    }

    public function filter(?\Closure $closure = null): static
    {
        if (is_null($closure)) {
            return static::fromArray(array_filter($this->items));
        }

        return static::fromArray(array_filter($this->items, fn ($item): mixed => $closure($item)));
    }

    public function usort(\Closure $closure): static
    {
        usort($this->items, fn ($a, $b) => $closure($a, $b));

        return static::fromArray($this->items);
    }

    public function slice(int $offset, ?int $length = null, bool $preserve_keys = false): static
    {
        return static::fromArray(
            array_slice($this->items, $offset, $length, $preserve_keys)
        );
    }

    /**
     * @return array<T>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param T $item
     */
    private function guardItemIsInstanceOfItemClassName($item): void
    {
        $itemClassName = $this->getItemClassName();
        if (!$item instanceof $itemClassName) {
            throw new \InvalidArgumentException(sprintf('Item must be an instance of %s', $itemClassName));
        }
    }
}
