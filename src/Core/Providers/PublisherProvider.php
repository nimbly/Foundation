<?php

namespace Nimbly\Foundation\Core\Providers;

use Nimbly\Carton\Container;
use UnexpectedValueException;
use Nimbly\Carton\ServiceProviderInterface;
use Nimbly\Syndicate\Adapter\PublisherInterface;
use Nimbly\Foundation\Consumer\Providers\FrameworkProvider;
use Nimbly\Syndicate\Filter\ValidatorFilter;
use Nimbly\Syndicate\Validator\JsonSchemaValidator;

/**
 * Provides a `Nimbly\Syndicate\PublisherInterface` instance to publish
 * events and messages to a queue or to a PubSub topic.
 *
 * @see `config/publisher.php` for configuration options.
 */
class PublisherProvider implements ServiceProviderInterface
{
	/**
	 * @inheritDoc
	 */
	public function register(Container $container): void
	{
		$container->singleton(
			PublisherInterface::class,
			function(Container $container): PublisherInterface {

				$publisher = FrameworkProvider::resolveAdapter(\config("publisher.adapter"), $container);

				if( $publisher instanceof PublisherInterface === false ){
					throw new UnexpectedValueException(
						\sprintf("Adapter \"%s\" is not a publisher.", $publisher::class)
					);
				}

				if( \config("publisher.schemas") ){
					$publisher = new ValidatorFilter(
						new JsonSchemaValidator(
							\config("publisher.schemas") ?? [],
							\config("publisher.ignore_missing_schemas") ?? false
						),
						$publisher
					);
				}

				return $publisher;
			}
		);
	}
}