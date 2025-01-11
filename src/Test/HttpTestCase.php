<?php

namespace Nimbly\Foundation\Test;

use JsonSerializable;
use Nimbly\Carton\Container;
use Nimbly\Capsule\HttpMethod;
use Nimbly\Limber\Application;
use PHPUnit\Framework\TestCase;
use Nimbly\Capsule\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class HttpTestCase extends TestCase
{
	/**
	 * Make a request to the HTTP application.
	 *
	 * @param string|HttpMethod $method
	 * @param string $endpoint
	 * @param array|JsonSerializable|null $body
	 * @param array<string,string> $headers
	 * @param array<string,string> $server_params
	 * @return ResponseInterface
	 */
	protected function makeRequest(
		string|HttpMethod $method,
		string $endpoint,
		array|JsonSerializable|null $body = null,
		array $headers = [],
		array $server_params = []): ResponseInterface
	{
		$server_request = new ServerRequest(
			method: $method,
			uri: "https://test.example.api/" . \trim($endpoint, "/"),
			body: !\is_null($body) ? \json_encode((object) $body, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_LINE_TERMINATORS) : null,
			headers: \array_merge(["Content-Type" => "application/json"], $headers),
			serverParams: $server_params
		);

		$response = $this->getApplication()->dispatch($server_request);

		return $response;
	}

	/**
	 * Get the HTTP Application instance.
	 *
	 * @return Application
	 */
	protected function getApplication(): Application
	{
		return Container::getInstance()->get(Application::class);
	}
}