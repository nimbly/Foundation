<?php

namespace Nimbly\Foundation\Core\Providers;

use Monolog\Handler\NoopHandler;
use Monolog\Logger;
use Nimbly\Carton\Container;
use Psr\Log\LoggerInterface;
use Nimbly\Carton\ServiceProviderInterface;

/**
 * Provides a `Psr\Log\LoggerInterface` instance to the dependency container.
 *
 * @see `config/logger.php` for configuration options.
 */
class LoggerProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->singleton(
			LoggerInterface::class,
			function(): LoggerInterface {
				return new Logger(
					name: \config("app.name"),
					handlers: \config("logger.enabled") ?
						(\config("logger.handlers") ?? []) :
						[new NoopHandler]
				);
			}
		);
	}
}