<?php

namespace Nimbly\Foundation\Core\Providers;

use Nimbly\Carton\Container;
use Nimbly\Announce\Dispatcher;
use Nimbly\Carton\ServiceProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the event disptacher instance to publish local events where
 * registered subscribers will receive a copy of the event.
 *
 * @see `config/event.php` for configuration options.
 */
class EventProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->singleton(
			EventDispatcherInterface::class,
			function(Container $container): EventDispatcherInterface {
				return new Dispatcher(
					\config("event.subscribers") ?? [],
					$container
				);
			}
		);
	}
}