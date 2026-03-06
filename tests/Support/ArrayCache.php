<?php

declare(strict_types=1);

namespace Tests\Psr16Storage\Support;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class ArrayCache implements CacheInterface
{
	/** @var array<string, mixed> */
	private array $items = [];

	/** @var list<array{key: string, value: mixed, ttl: null|int|DateInterval}> */
	public array $setCalls = [];

	/** @var list<string> */
	public array $deleteCalls = [];

	/** @var list<list<string>> */
	public array $deleteMultipleCalls = [];

	public bool $clearCalled = false;

	public function get(string $key, mixed $default = null): mixed
	{
		return $this->items[$key] ?? $default;
	}

	public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
	{
		$this->items[$key] = $value;
		$this->setCalls[] = [
			'key' => $key,
			'value' => $value,
			'ttl' => $ttl,
		];

		return true;
	}

	public function delete(string $key): bool
	{
		unset($this->items[$key]);
		$this->deleteCalls[] = $key;

		return true;
	}

	public function clear(): bool
	{
		$this->items = [];
		$this->clearCalled = true;

		return true;
	}

	public function getMultiple(iterable $keys, mixed $default = null): iterable
	{
		$result = [];
		foreach ($keys as $key) {
			$result[$key] = $this->items[$key] ?? $default;
		}

		return $result;
	}

	public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
	{
		foreach ($values as $key => $value) {
			$this->set((string) $key, $value, $ttl);
		}

		return true;
	}

	public function deleteMultiple(iterable $keys): bool
	{
		$deleted = [];
		foreach ($keys as $key) {
			unset($this->items[$key]);
			$deleted[] = (string) $key;
		}

		$this->deleteMultipleCalls[] = $deleted;

		return true;
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->items);
	}
}
