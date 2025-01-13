<?php

namespace Nimbly\Foundation\Http\Middleware;

use Nimbly\Foundation\Core\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This middleware will log each incoming request and outgoing response.
 *
 * The bodies of the request and responses are redacted.
 */
class RequestLoggerMiddleware implements MiddlewareInterface
{
	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		// Build a unique request ID to associate matching request and responses
		$request_id = \bin2hex(\random_bytes(8));

		$endpoint = \sprintf(
			"%s#/%s",
			$request->getMethod(),
			\trim($request->getUri()->getPath(), "/")
		);

		$message = \sprintf(
			"[REQUEST] (%s) => %s",
			$request_id,
			$endpoint
		);

		$request_context = [
			"method" => $request->getMethod(),
			"uri" => (string) $request->getUri(),
			"ip" => $request->getHeaderLine("X_FORWARDED_FOR") ?: $request->getServerParams()["REMOTE_ADDR"] ?? "",
			"headers" => $request->getHeaders(),
		];

		if( \config("app.debug") ){
			$request_context["body"] = (array) $request->getParsedBody();
		}

		Log::debug($message, $request_context);

		$response = $handler->handle($request);

		// Build the response log message
		$message = \sprintf(
			"[RESPONSE] (%s) => [%s %s] %s",
			$request_id,
			$response->getStatusCode(),
			$response->getReasonPhrase(),
			$endpoint
		);

		$response_context = [
			"headers" => $response->getHeaders()
		];

		if( \config("app.debug") ){
			$body = clone $response->getBody();
			$response_context["body"] = $response->getBody()->getContents();
			$response = $response->withBody($body);
		}

		Log::debug($message, $response_context);

		return $response;
	}
}