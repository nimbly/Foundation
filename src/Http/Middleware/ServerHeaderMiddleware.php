<?php

namespace Nimbly\Foundation\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This middleware adds a Server response header to all outgoing responses.
 *
 * The contents of this header is designed to contain the name of your service
 * and its version.
 *
 * For example:
 * 		`Server: FooService/1.3.17`
 *
 * This is especially helpful when tracing or debugging HTTP calls to your services
 * to identify which service and version are actually responding.
 *
 * See `config/app.php` for more information on versioning your service.
 */
class ServerHeaderMiddleware implements MiddlewareInterface
{
	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = $handler->handle($request);

		return $response->withHeader(
			"Server",
			\config("app.name") . "/" . \config("app.version")
		);
	}
}