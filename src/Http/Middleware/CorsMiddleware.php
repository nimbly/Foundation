<?php

namespace Nimbly\Foundation\Http\Middleware;

use Nimbly\Capsule\Response;
use Nimbly\Foundation\Core\Attributes\Config;
use Nimbly\Capsule\HttpMethod;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Limber\Router\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This middleware adds CORS support to your API.
 *
 * This middleware will not pass the request to the next handler and as such
 * should be the last middleware in the chain.
 */
class CorsMiddleware implements MiddlewareInterface
{
	/**
	 * @param Router $router The router instance used to determine supported methods.
	 */
	public function __construct(
		protected Router $router,

		#[Config("http.cors.allowed_origins")]
		protected array $allowed_origins = [],

		#[Config("http.cors.allowed_headers")]
		protected array $allowed_headers = [],

		#[Config("http.cors.allow_credentials")]
		protected bool $allow_credentials = true,
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if( $request->getMethod() !== HttpMethod::OPTIONS->value ){
			$response = $handler->handle($request);
		}
		else {
			$response = new Response(
				statusCode: ResponseStatus::NO_CONTENT,
				headers: [
					"Allow" => \implode(", ", \array_unique(["OPTIONS", ...$this->router->getSupportedMethods($request)]))
				]
			);
		}

		if( $request->hasHeader("Origin") ){
			$origin = $request->getHeaderLine("Origin");

			if( \in_array($origin, $this->allowed_origins) ){
				$response = $response
				->withHeader("Access-Control-Allow-Origin", $origin)
				->withHeader("Vary", "Origin");
			}
			elseif( \in_array("*", $this->allowed_origins) ){
				$response = $response
				->withHeader("Access-Control-Allow-Origin", "*")
				->withHeader("Vary", "Origin");
			}

			$response = $response->withHeader(
				"Access-Control-Allow-Credentials",
				$this->allow_credentials ? "true" : "false"
			);

			$response = $response->withHeader(
				"Access-Control-Allow-Headers",
				\implode(", ", \array_unique([
					"Accept", "Accept-Language", "Content-Language", "Content-Type", "Range",
					...$this->allowed_headers
				]))
			);

			if( $request->getMethod() === HttpMethod::OPTIONS->value ){
				$response = $response->withHeader(
					"Access-Control-Allow-Methods",
					\implode(", ", \array_unique(["OPTIONS", ...$this->router->getSupportedMethods($request)]))
				);
			}
		}

		return $response;
	}
}
