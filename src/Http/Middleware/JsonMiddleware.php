<?php

namespace Nimbly\Foundation\Http\Middleware;

use Nimbly\Capsule\HttpMethod;
use Nimbly\Limber\Exceptions\BadRequestHttpException;
use Nimbly\Limber\Exceptions\NotAcceptableHttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This middleware requires all incoming server requests with bodies (POST, PUT, PATCH) must be
 * of Content-Type "application/json". If the content type does not match, a `NotAcceptableHttpException`
 * is thrown (406 Not Acceptable).
 *
 * This middleare will also attempt to decode the JSON body and put its contents into the
 * `ServerRequest`'s parsed body.
 */
class JsonMiddleware implements MiddlewareInterface
{
	/**
	 * @param boolean $decode_as_array Decode the JSON body as an associative array. If false the body will be decoded as an object.
	 */
	public function __construct(
		protected bool $decode_as_array = true
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if( \in_array(HttpMethod::tryFrom(\strtoupper($request->getMethod())), [HttpMethod::POST, HttpMethod::PUT, HttpMethod::PATCH]) &&
			$request->getParsedBody() === null &&
			$request->getBody()->getSize() ){

			$content_type = \trim($request->getHeaderLine("Content-Type"));

			if( \stripos($content_type, "application/json") === false ){
				throw new NotAcceptableHttpException(
					\sprintf("Content type \"%s\" is not supported.", $content_type)
				);
			}

			$body = (clone $request->getBody())->getContents();

			if( empty($body) ){
				$parsed_body = [];
			}
			else {
				$parsed_body = \json_decode($body, $this->decode_as_array);

				if( \json_last_error() !== JSON_ERROR_NONE ) {
					throw new BadRequestHttpException("Invalid JSON request body.");
				}
			}

			$request = $request->withParsedBody($parsed_body);
		}

		return $handler->handle($request);
	}
}