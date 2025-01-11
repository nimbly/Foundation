<?php

namespace Nimbly\Foundation\Core\Providers;

use Predis\Client;
use Nimbly\Carton\Container;
use UnexpectedValueException;
use Nimbly\Carton\ServiceProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Provides a Symfony\Contracts\Cache\CacheInterface instance to be
 * used in global caching.
 *
 * @see `config/cache.php` for configuration options.
 */
class CacheProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->singleton(
			CacheInterface::class,
			function(): CacheInterface {
				return match( \config("cache.adapter") ){
					"apcu" => new ApcuAdapter(
						namespace: \config("cache.namespace") ?? "",
						defaultLifetime: \config("cache.default_ttl") ?: 0,
					),

					"file" => new FilesystemAdapter(
						namespace: \config("cache.namespace") ?? "",
						directory: \config("cache.directory"),
						defaultLifetime: \config("cache.default_ttl") ?: 0,
					),

					"memory" => new ArrayAdapter,

					"null" => new NullAdapter,

					"pdo" => new PdoAdapter(
						connOrDsn: \config("cache.connection"),
						namespace: \config("cache.namespace") ?? "",
						defaultLifetime: \config("cache.default_ttl") ?: 0,
					),

					"redis" => new RedisAdapter(
						new Client(\config("cache.connection")),
						namespace: \config("cache.namespace") ?? "",
						defaultLifetime: \config("cache.default_ttl") ?: 0,
					),

					default => throw new UnexpectedValueException("Cache adapter not supported: " . \config("cache.adapter"))
				};
			}
		);
	}
}