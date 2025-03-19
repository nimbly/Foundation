<?php

namespace Nimbly\Foundation\Http\Middleware;

use Nimbly\Capsule\Response;
use Nimbly\Capsule\HttpMethod;
use Nimbly\Capsule\ResponseStatus;
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
	 * @param array<string> $allowed_origins A list of allowed origins as a hostname. Eg, https://api.example.com
	 * @param array<string> $allowed_methods A list of HTTP methods allowed to be used. By default, GET, HEAD, and POST are always allowed accoring to CORS safelisted methods.
	 * @param array<string> $allowed_headers A list of HTTP request headers allowed to be sent. By default, Accept, Accept-Language, Content-Language, Content-Type, and Range are always allowed according to CORS safelisted headers.
	 * @param bool $allow_credentials Whether credentials are allowed to be passed with requests.
	 */
	public function __construct(
		protected array $allowed_origins,
		protected array $allowed_methods = [],
		protected array $allowed_headers = [],
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
					"Allow" => \implode(", ", ["OPTIONS", "GET", "HEAD", "POST", ...$this->allowed_methods])
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
				\implode(", ", [
					"Accept", "Accept-Language", "Content-Language", "Content-Type", "Range",
					...$this->allowed_headers
				])
			);

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
