<?php

namespace Nimbly\Foundation\Http\Providers;

use Nimbly\Carton\Container;
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
	public function register(Container $container): void
	{
		$validatorBuilder = new ValidatorBuilder;

		// $validatorBuilder->setCache(
		// 	$container->get(CacheInterface::class),
		// 	86400
		// );

		// $validatorBuilder->overrideCacheKey(
		// 	\sprintf(
		// 		"%s-%s-openapi_schema",
		// 		\config("app.name"),
		// 		\config("app.version")
		// 	)
		// );

		$validatorBuilder->fromJsonFile(\config("http.schema"));

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