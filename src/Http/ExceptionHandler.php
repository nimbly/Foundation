<?php

namespace Nimbly\Foundation\Http;

use Nimbly\Foundation\Core\Log;
use Nimbly\Foundation\Http\JsonResponse;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Limber\ExceptionHandlerInterface;
use Nimbly\Limber\Exceptions\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * The default exception handler that will return a well formatted JSON error response
 * for any uncaught exceptions.
 *
 * Exceptions that extend `Nimbly\Limber\Exceptions\HttpException` will automatically set
 * the correct HTTP response code.
 *
 * Exceptions that do not extend `Nimbly\Limber\Exceptions\HttpException` will return a
 * `500 Interal Server Error` response.
 *
 * If debug mode is enabled (@see config/app.php), additional information is returned in
 * this response including a full stack trace.
 */
class ExceptionHandler implements ExceptionHandlerInterface
{
	public function __construct(
		protected string $default_message = "There was an issue processing your request.",
		protected bool $debug = false)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
	{
		$status_code = $this->getStatusCode($exception);

		if( $status_code->value >= ResponseStatus::INTERNAL_SERVER_ERROR->value ) {
			Log::critical(
				$exception->getMessage(),
				[
					"method" => $request->getMethod(),
					"uri" => (string) $request->getUri(),
					"code" => (int) $status_code,
					"file" => $exception->getFile(),
					"line" => $exception->getLine()
				]
			);
		}

		$body = [
			"code" => (int) $exception->getCode(),
			"message" => $status_code->value < ResponseStatus::INTERNAL_SERVER_ERROR->value ?
				$exception->getMessage() :
				$this->default_message
		];

		if( $this->debug ) {
			$body["debug"] = [
				"message" => $exception->getMessage(),
				"file" => $exception->getFile(),
				"line" => $exception->getLine(),
				"trace" => $exception->getTraceAsString()
			];

			if( $exception->getPrevious() instanceof ValidationFailed &&
				$exception->getPrevious()->getPrevious() instanceof SchemaMismatch ){
				$body["debug"]["details"] = $this->buildBreadcrumb($exception->getPrevious()->getPrevious());
			}
		}

		return new JsonResponse(
			$status_code,
			$body,
			$exception instanceof HttpException ? $exception->getHeaders() : []
		);
	}

	/**
	 * Get the HTTP status code to use.
	 *
	 * @param Throwable $exception
	 * @return ResponseStatus
	 */
	protected function getStatusCode(Throwable $exception): ResponseStatus
	{
		if( $exception instanceof HttpException ){
			return ResponseStatus::from($exception->getHttpStatus());
		}

		return ResponseStatus::INTERNAL_SERVER_ERROR;
	}

	/**
	 * Parse the schema mismatch exception to pull out the JSON path to the issue.
	 *
	 * @param SchemaMismatch $exception
	 * @return array<array{message:string,location:string,data:mixed}>
	 */
	protected function buildBreadcrumb(SchemaMismatch $exception): array
	{
		$messages = [];
		while( $exception ) {
			$messages[] = [
				"message" => $exception->getMessage(),
				"location" => \implode("/", $exception->dataBreadCrumb()?->buildChain()),
				"data" => $exception->data()
			];

			$exception = $exception->getPrevious();
		};

		return $messages;
	}
}