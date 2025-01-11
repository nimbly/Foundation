<?php

namespace Nimbly\Foundation\Http\Middleware;

use Nimbly\Capsule\HttpMethod;
use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Limber\Router\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This middleware will respond to any incoming OPTIONS call and return the allowed methods for
 * requested path/endpoint. It will not pass along the request to the next handler, so make sure
 * it is the LAST middleware processed before the Kernel.
 */
class OptionsMiddleware implements MiddlewareInterface
{
	public function __construct(
		protected Router $router)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if( $request->getMethod() !== HttpMethod::OPTIONS->value ){
			return $handler->handle($request);
		}

		return new Response(
			statusCode: ResponseStatus::NO_CONTENT,
			headers: [
				"Allow" => \implode(", ", \array_merge(["OPTIONS"], $this->router->getSupportedMethods($request)))
			]
		);
	}
}
