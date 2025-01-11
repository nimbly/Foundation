<?php

namespace Nimbly\Foundation\Core;

use DateInterval;
use UnexpectedValueException;
use Psr\SimpleCache\CacheInterface;

/**
 * @method static bool has(string $key) Test whether cache contains an item by its key.
 * @method static mixed get(string $key) Get a cache item by its key.
 * @method static void set(string $key, mixed $data, int|DateInterval|null $ttl = null) Set a cache item with a TTL.
 * @method static void delete(string $key) Delete a cache item by its key.
 * @method static void clear() Clear all contents of the cache.
 */
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
	 * Call instance method on cache instance.
	 *
	 * @param string $method
	 * @param array<mixed> $params
	 * @throws NotFoundException
	 * @return mixed
	 */
	public static function __callStatic(string $method, array $params): mixed
	{
		if( self::$cache === null ){
			throw new UnexpectedValueException("Cache class has not been initialized. Please call Cache::init() method first.");
		}

		return \call_user_func_array(
			[self::$cache, $method],
			$params
		);
	}

	/**
	 * Get or create a cache entry.
	 *
	 * @param string $key
	 * @param callable $builder
	 * @param DateInterval|null $ttl
	 * @return mixed
	 */
	public static function entry(string $key, callable $builder, ?DateInterval $ttl = null): mixed
	{
		$data = self::get($key);

		if( !empty($data) ){
			return $data;
		}

		$data = \call_user_func($builder, $key);

		self::set($key, $data, $ttl);

		return $data;
	}
}