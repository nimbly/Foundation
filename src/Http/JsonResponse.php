<?php

namespace Nimbly\Foundation\Http;

use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use JsonSerializable;
use UnexpectedValueException;

/**
 * A JSON HTTP response that will automatically JSON encode the body and
 * set the response Content-Type to "application/json".
 */
class JsonResponse extends Response
{
	/**
	 * @param int|ResponseStatus $status_code HTTP response status code.
	 * @param array<array-key,mixed>|JsonSerializable $body Response body as an associative array or an object implementing JsonSerializable.
	 * @param array<array-key,string> $headers Additional headers to include with response.
	 */
	public function __construct(
		int|ResponseStatus $status_code = ResponseStatus::OK,
		array|JsonSerializable $body = [],
		array $headers = []
	)
	{
		$encoded_body = \json_encode($body, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

		if( \json_last_error() !== JSON_ERROR_NONE ){
			throw new UnexpectedValueException("Response body could not be serialized into JSON.");
		}

		parent::__construct(
			$status_code,
			$encoded_body,
			["Content-Type" => "application/json", ...$headers]
		);
	}
}