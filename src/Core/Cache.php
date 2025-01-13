<?php

namespace Nimbly\Foundation\Core;

use Symfony\Contracts\Cache\CacheInterface;
use UnexpectedValueException;

class Cache
{
	private static ?CacheInterface $cache = null;

	/**
	 * Initialize the cache with the CacheInterface instance.
	 *
	 * @param CacheInterface $cache
	 * @return void
	 */
	public static function init(CacheInterface $cache): void
	{
		if( self::$cache === null ){
			self::$cache = $cache;
		}
	}

	/**
	 * Get or create a cache entry.
	 *
	 * @param string $key
	 * @param callable $callback
	 * @param float|null $beta
	 * @param array|null $metadata
	 * @return mixed
	 */
	public static function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
	{
		if( empty(self::$cache) ){
			throw new UnexpectedValueException("Cache has not been initialized. Please call Cache::init() first.");
		}

		return self::$cache->get($key, $callback, $beta, $metadata);
	}

	/**
	 * Delete a key from the cache.
	 *
	 * @param string $key
	 * @return void
	 */
	public static function delete(string $key): bool
	{
		if( empty(self::$cache) ){
			throw new UnexpectedValueException("Cache has not been initialized. Please call Cache::init() first.");
		}

		return self::$cache->delete($key);
	}
}