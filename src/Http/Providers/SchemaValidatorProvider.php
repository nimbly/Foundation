<?php

namespace Nimbly\Foundation\Http\Providers;

use Nimbly\Carton\Container;
use Psr\Cache\CacheItemPoolInterface;
use Nimbly\Carton\ServiceProviderInterface;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ServerRequestValidator;

/**
 * Provides the implementations necessary to validate incoming HTTP requests and
 * outgoing HTTP responses against your OpenAPI schema.
 *
 * @see `config/http.php` for configuration options.
 */
class SchemaValidatorProvider implements ServiceProviderInterface
{
	/**
	 * @inheritDoc
	 */
	public function register(Container $container): void
	{
		$validatorBuilder = new ValidatorBuilder;

		$file = \config("http.schema.file");

		if( \str_ends_with(\strtolower($file), ".yml") ||
			\str_ends_with(\strtolower($file), ".yaml") ){
			$validatorBuilder->fromYamlFile($file);
		}
		else {
			$validatorBuilder->fromJsonFile($file);
		}

		if( \config("http.schema.cache.enabled") ){
			$validatorBuilder->setCache(
				$container->get(CacheItemPoolInterface::class),
				\config("http.schema.cache.ttl") ?? 86400
			);

			if( \config("http.schema.cache.key") ){
				$validatorBuilder->overrideCacheKey(\config("http.schema.cache.key"));
			}
		}

		$container->set(
			ServerRequestValidator::class,
			$validatorBuilder->getServerRequestValidator()
		);

		$container->set(
			ResponseValidator::class,
			$validatorBuilder->getResponseValidator()
		);
	}
}