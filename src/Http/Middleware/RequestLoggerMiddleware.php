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

		// Log the request message
		Log::debug($message, [
			"method" => $request->getMethod(),
			"uri" => (string) $request->getUri(),
			"ip" => $request->getServerParams()["REMOTE_ADDR"] ?? "",
			"host" => $request->getUri()->getHost(),
			"headers" => $request->getHeaders(),
			"query" => $request->getQueryParams(),
		]);

		$response = $handler->handle($request);

		// Build the response log message
		$message = \sprintf(
			"[RESPONSE] (%s) => [%s %s] %s",
			$request_id,
			$response->getStatusCode(),
			$response->getReasonPhrase(),
			$endpoint
		);

		Log::debug($message, ["headers" => $response->getHeaders()]);

		return $response;
	}
}