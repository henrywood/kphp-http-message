<?php

namespace PhpPkg\Http\Message;

class Collection
{
	private $items = [];

	public function __construct(array $items = [])
	{
		$this->items = $items;
	}

	/**
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return mixed|null
	 */
	public function add(string $name, mixed $value): mixed
	{
		if (isset($this->items[$name])) {
			return $this;
		}

		$this->items[$name] = $value;

		return $this;
	}

	// Get item by key
	public function get(string $key)
	{
		return $this->items[$key] ?? null;
	}

	// Set item by key
	public function set(string $key, $value): void
	{
		$this->items[$key] = $value;
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
	public function __toString(): string
	{
		return json_encode($this->items);
	}
}


