<?php

namespace Nimbly\Foundation\Core\Providers;

use Predis\Client;
use Nimbly\Carton\Container;
use UnexpectedValueException;
use Psr\Cache\CacheItemPoolInterface;
use Nimbly\Carton\ServiceProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;

/**
 * Provides a `Psr\Cache\CacheItemPoolInterface` instance to be
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
			function(Container $container): CacheInterface {
				return $this->resolveAdapter(\config("cache.adapter"), $container);
			},
			CacheItemPoolInterface::class
		);
	}

	/**
	 * Resolve the cache adapter.
	 *
	 * @param string $adapter
	 * @param Container $container
	 * @return CacheInterface
	 */
	protected function resolveAdapter(string $adapter, Container $container): CacheInterface
	{
		return match( $adapter ){
			"apcu" => new ApcuAdapter(
				namespace: \config("cache.namespace") ?? "",
				defaultLifetime: \config("cache.default_ttl") ?: 0,
				marshaller: $container->has(MarshallerInterface::class) ?
					$container->get(MarshallerInterface::class) :
					(\config("cache.marshaller") ?: null)
			),

			"file" => new FilesystemAdapter(
				namespace: \config("cache.namespace") ?? "",
				defaultLifetime: \config("cache.default_ttl") ?: 0,
				directory: \config("cache.path"),
				marshaller: $container->has(MarshallerInterface::class) ?
					$container->get(MarshallerInterface::class) :
					(\config("cache.marshaller") ?: null)
			),

			"pdo" => new PdoAdapter(
				connOrDsn: \config("cache.connection"),
				namespace: \config("cache.namespace") ?? "",
				defaultLifetime: \config("cache.default_ttl") ?: 0,
				marshaller: $container->has(MarshallerInterface::class) ?
					$container->get(MarshallerInterface::class) :
					(\config("cache.marshaller") ?: null)
			),

			"redis" => new RedisAdapter(
				redis: new Client(\config("cache.connection")),
				namespace: \config("cache.namespace") ?? "",
				defaultLifetime: \config("cache.default_ttl") ?: 0,
				marshaller: $container->has(MarshallerInterface::class) ?
					$container->get(MarshallerInterface::class) :
					(\config("cache.marshaller") ?: null)
			),

			"memcache" => new MemcachedAdapter(
				client: new \Memcached(\config("cache.connection")),
				namespace: \config("cache.namespace") ?? "",
				defaultLifetime: \config("cache.default_ttl") ?: 0,
				marshaller: $container->has(MarshallerInterface::class) ?
					$container->get(MarshallerInterface::class) :
					(\config("cache.marshaller") ?: null)
			),

			"memory" => new ArrayAdapter,
			"null" => new NullAdapter,

			default => throw new UnexpectedValueException(
				\sprintf("Cache adapter \"%s\" not supported.", $adapter)
			)
		};
	}
}