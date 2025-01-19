<?php

namespace Nimbly\Foundation\Http\Middleware;

use Nimbly\Capsule\HttpMethod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This middleware adds CORS support to your API.
 */
class CorsMiddleware implements MiddlewareInterface
{
	/**
	 * @param array<string> $allowed_origins
	 */
	public function __construct(
		protected array $allowed_origins
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = $handler->handle($request);

		if( $request->hasHeader("Origin") ){
			$origin = $request->getHeaderLine("Origin");

			if( \in_array($origin, $this->allowed_origins) ){
				$response = $response
				->withHeader("Access-Control-Allow-Origin", $origin)
				->withHeader("Vary", "Origin");
			}

			$response = $response->withHeader("Access-Control-Allow-Headers", "Origin, Content-type, Authorization");

			if( $request->getMethod() === HttpMethod::OPTIONS->value ){
				$response = $response->withHeader(
					"Access-Control-Allow-Methods",
					$response->getHeaderLine("Allow")
				);
			}
		}

		return $response;
	}
}
