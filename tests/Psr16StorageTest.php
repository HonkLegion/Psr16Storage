<?php

declare(strict_types=1);

namespace Tests\Psr16Storage;

use HonkLegion\Psr16Storage\Psr16Storage;
use Nette;
use Nette\Caching\Cache;
use PHPUnit\Framework\TestCase;
use Tests\Psr16Storage\Support\ArrayCache;

class Psr16StorageTest extends TestCase
{
	public function testReadReturnsNullForMissingKey(): void
	{
		$storage = new Psr16Storage(new ArrayCache());

		self::assertNull($storage->read('missing'));
	}

	public function testReadReturnsStoredData(): void
	{
		$cache = new ArrayCache();
		$cache->set('item', ['data' => 'value']);
		$storage = new Psr16Storage($cache);

		self::assertSame('value', $storage->read('item'));
	}

	public function testReadDeletesWhenCallbackFails(): void
	{
		$cache = new ArrayCache();
		$cache->set('item', [
			'data' => 'value',
			'callbacks' => [[static fn(): bool => false]],
		]);
		$storage = new Psr16Storage($cache);

		self::assertNull($storage->read('item'));
		self::assertSame(['item'], $cache->deleteCalls);
	}

	public function testReadRefreshesSlidingTtl(): void
	{
		$cache = new ArrayCache();
		$cache->set('item', [
			'data' => 'value',
			'delta' => 15,
		]);
		$storage = new Psr16Storage($cache);
		$before = time();

		self::assertSame('value', $storage->read('item'));

		$after = time();
		$refresh = $cache->setCalls[1] ?? null;
		self::assertNotNull($refresh);
		self::assertSame('item', $refresh['key']);
		self::assertGreaterThanOrEqual($before + 15, $refresh['ttl']);
		self::assertLessThanOrEqual($after + 15, $refresh['ttl']);
	}

	public function testWriteThrowsOnDependentItems(): void
	{
		$storage = new Psr16Storage(new ArrayCache());

		$this->expectException(Nette\NotSupportedException::class);
		$storage->write('item', 'value', [Cache::Items => ['another']]);
	}

	public function testWriteThrowsOnJournalDependencies(): void
	{
		$storage = new Psr16Storage(new ArrayCache());

		$this->expectException(Nette\InvalidStateException::class);
		$storage->write('item', 'value', [Cache::Tags => ['tag']]);
	}

	public function testWriteCapsExpirationWithMaxTtl(): void
	{
		$cache = new ArrayCache();
		$storage = new Psr16Storage($cache, 30);
		$before = time();

		$storage->write('item', 'value', [Cache::Expire => $before + 120]);

		$after = time();
		$write = $cache->setCalls[0] ?? null;
		self::assertNotNull($write);
		self::assertGreaterThanOrEqual($before + 30, $write['ttl']);
		self::assertLessThanOrEqual($after + 30, $write['ttl']);
	}

	public function testWriteStoresSlidingAndCallbacks(): void
	{
		$cache = new ArrayCache();
		$storage = new Psr16Storage($cache);
		$callback = static fn(): bool => true;

		$storage->write('item', 'value', [
			Cache::Expire => 45,
			Cache::Sliding => true,
			Cache::Callbacks => [[$callback]],
		]);

		$write = $cache->setCalls[0] ?? null;
		self::assertNotNull($write);
		self::assertSame(45, $write['ttl']);
		self::assertSame('value', $write['value']['data']);
		self::assertSame(45, $write['value']['delta']);
		self::assertSame([[$callback]], $write['value']['callbacks']);
	}

	public function testRemoveDeletesKey(): void
	{
		$cache = new ArrayCache();
		$storage = new Psr16Storage($cache);

		$storage->remove('item');

		self::assertSame(['item'], $cache->deleteCalls);
	}

	public function testCleanAllClearsCache(): void
	{
		$cache = new ArrayCache();
		$storage = new Psr16Storage($cache);

		$storage->clean([Cache::All => true]);

		self::assertTrue($cache->clearCalled);
	}

	public function testBulkReadReturnsMappedResultsAndNullForMisses(): void
	{
		$cache = new ArrayCache();
		$cache->set('1', ['data' => 'one']);
		$storage = new Psr16Storage($cache);

		$result = $storage->bulkRead([1, 'missing']);

		self::assertSame([1 => 'one', 'missing' => null], $result);
	}

	public function testBulkReadDeletesInvalidCallbacksAndRefreshesSliding(): void
	{
		$cache = new ArrayCache();
		$cache->set('bad', [
			'data' => 'x',
			'callbacks' => [[static fn(): bool => false]],
		]);
		$cache->set('slide', [
			'data' => 'ok',
			'delta' => 10,
		]);
		$storage = new Psr16Storage($cache);
		$before = time();

		$result = $storage->bulkRead(['bad', 'slide']);

		$after = time();
		self::assertSame(['slide' => 'ok'], $result);
		self::assertSame([['bad']], $cache->deleteMultipleCalls);

		$refresh = $cache->setCalls[2] ?? null;
		self::assertNotNull($refresh);
		self::assertSame('slide', $refresh['key']);
		self::assertGreaterThanOrEqual($before + 10, $refresh['ttl']);
		self::assertLessThanOrEqual($after + 10, $refresh['ttl']);
	}
}
