<?php

namespace PhpPkg\Http\Message\Util;

class Collection
{
	private $items = [];

	public function __construct(array $items = [])
	{
		$this->items = $items;
	}

	/**
	 * @param string $name
	 *
	 * @return self
	 */
	public function add(string $name, $value): Collection
	{
		if (isset($this->items[$name])) {
			return $this;
		}

		$this->items[$name] = $value;

		return $this;
	}

	/**
	 * @param array $values
	 * @return static
	 */
	public function sets(array $values): static
	{
		$this->items = $values;
		return $this;
	}

	// Get item by key
	public function get(string $key, ?string $default = NULL)
	{
		return $this->items[$key] ?? $default;
	}

	// Set item by key
	// @return static
	public function set(string $key, mixed $value): static
	{
		$this->items[$key] = $value;
		return $this;
	}

	// Check if a key exists
	public function has(string $key): bool
	{
		return array_key_exists($key, $items);
	}

	// Remove item by key
	public function remove(string $key): void
	{
		unset($this->items[$key]);
	}

	// Get all items
	public function all(): array
	{
		return $this->items;
	}

	// Get the count of items
	public function count(): int
	{
		return count($this->items);
	}

	// Clear all items
	public function clear(): void
	{
		$this->items = [];
	}

	// Check if collection is empty
	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	// Get keys of the collection
	public function keys(): array
	{
		return array_keys($this->items);
	}

	public function toArray() {
		return $this->items;
	}

	// Convert collection to JSON string
	public function toJson(): string
	{
		return json_encode($this->items);
	}

	// Convert collection to a string
	public function __toString(): string|false
	{
		return json_encode($this->items);
	}
}


