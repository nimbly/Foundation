<?php

namespace Nimbly\Foundation\Core\Providers;

use Nimbly\Carton\Container;
use UnexpectedValueException;
use Nimbly\Carton\ServiceProviderInterface;
use Nimbly\Foundation\Consumer\Providers\ApplicationProvider;

/**
 * Provides a PublisherInterface instance to publish events and messages to a queue or to a
 * PubSub topic.
 *
 * @see `config/publisher.php` for configuration options.
 */
class PublisherProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->singleton(
			PublisherInterface::class,
			function(Container $container): PublisherInterface {

				$publisher = ApplicationProvider::resolveAdapter(\config("publisher.adapter"), $container);

				if( $publisher instanceof PublisherInterface === false ){
					throw new UnexpectedValueException("Unsupported publisher adapter: " . $publisher::class);
				}

				return $publisher;
			}
		);
	}
}