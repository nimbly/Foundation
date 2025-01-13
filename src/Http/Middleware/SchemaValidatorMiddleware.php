<?php

namespace Nimbly\Foundation\Http\Middleware;

use Nimbly\Limber\Router\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use League\OpenAPIValidation\PSR7\Exception\NoPath;
use Nimbly\Limber\Exceptions\NotFoundHttpException;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use Nimbly\Limber\Exceptions\BadRequestHttpException;
use Nimbly\Limber\Exceptions\UnauthorizedHttpException;
use League\OpenAPIValidation\PSR7\Exception\NoOperation;
use League\OpenAPIValidation\PSR7\ServerRequestValidator;
use League\OpenAPIValidation\Schema\Exception\TypeMismatch;
use Nimbly\Limber\Exceptions\MethodNotAllowedHttpException;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use Nimbly\Limber\Exceptions\InternalServerErrorHttpException;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidSecurity;

/**
 * This middleware validates each incoming server request and outgoing response against your
 * OpenAPI schema.
 *
 * If the incoming request fails validation, a 4xx level HTTP exception will be thrown.
 *
 * If the outgoing response fails validation, a 500 Internal Server Error exception
 * will be thrown.
 */
class SchemaValidatorMiddleware implements MiddlewareInterface
{
	/**
	 * @param ServerRequestValidator $serverRequestValidator
	 * @param ResponseValidator $responseValidator
	 */
	public function __construct(
		protected ServerRequestValidator $serverRequestValidator,
		protected ResponseValidator $responseValidator
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if( \config("http.schema.enabled") === false ){
			return $handler->handle($request);
		}

		try {

			$operationAddress = $this->serverRequestValidator->validate($request);
		}
		catch( TypeMismatch $typeMismatchException ){
			throw new BadRequestHttpException(
				message: $typeMismatchException->getMessage(),
				previous: $typeMismatchException
			);
		}
		catch( NoOperation $noOperation ){
			throw new MethodNotAllowedHttpException(
				methodsAllowed: $request->getAttribute(Route::class)->getMethods(),
				message: $noOperation->getMessage(),
				previous: $noOperation
			);
		}
		catch( NoPath $noPath ){
			throw new NotFoundHttpException(
				message: $noPath->getMessage(),
				previous: $noPath
			);
		}
		catch( InvalidSecurity $invalidSecurity ){
			throw new UnauthorizedHttpException(
				authMethod: "Bearer",
				message: "Please authenticate to continue",
				previous: $invalidSecurity
			);
		}
		catch( ValidationFailed $validationFailedException ){
			throw new BadRequestHttpException(
				message: $validationFailedException->getMessage(),
				previous: $validationFailedException
			);
		}

		$response = $handler->handle($request);

		// We need to clone the response body because the OpenApi validator will
		// drain the response stream.
		$response_body = clone $response->getBody();

		try {

			$this->responseValidator->validate($operationAddress, $response);
		}
		catch( TypeMismatch $typeMismatchException ){
			throw new InternalServerErrorHttpException(
				message: "Response does not adhere to OpenAPI schema.",
				previous: $typeMismatchException
			);
		}

		return $response->withBody($response_body);
	}
}