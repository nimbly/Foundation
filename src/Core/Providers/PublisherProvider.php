<?php

namespace Nimbly\Foundation\Core\Providers;

use Nimbly\Carton\Container;
use UnexpectedValueException;
use Nimbly\Syndicate\PublisherInterface;
use Nimbly\Carton\ServiceProviderInterface;
use Nimbly\Foundation\Consumer\Providers\ApplicationProvider;

/**
 * Provides a `Nimbly\Syndicate\PublisherInterface` instance to publish
 * events and messages to a queue or to a PubSub topic.
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
					throw new UnexpectedValueException(
						\sprintf("Adapter \"%s\" is not a publisher.", $publisher::class)
					);
				}

				return $publisher;
			}
		);
	}
}