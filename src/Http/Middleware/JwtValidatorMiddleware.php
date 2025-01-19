<?php

namespace Nimbly\Foundation\Http\Middleware;

use Nimbly\Proof\Proof;
use Nimbly\Proof\Token;
use Psr\Http\Message\ResponseInterface;
use Nimbly\Proof\TokenDecodingException;
use Psr\Http\Server\MiddlewareInterface;
use Nimbly\Proof\SignerNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nimbly\Limber\Exceptions\UnauthorizedHttpException;

/**
 * This middleware extracts and decodes a JWT bearer token in the Authorization header
 * of a request if it is present. The decoded token will be attached to the ServerRequest
 * instance as an attribute with key Nimbly\Proof\Token.
 *
 * PLEASE NOTE: This middleware does not enforce that a valid token is *required* for
 * the request, only that if a token *is* present that it is a valid token.
 *
 * If the token is not valid, an `UnauthorizedHttpException` (401 Unauthorized) is thrown.
 *
 * Some reasons why a token may not be valid:
 *  - Token not a signed JWT
 * 	- Malformed JSON header or payload
 * 	- Token signature mismatch
 * 	- Token is expired (exp claim)
 * 	- Token is not yet ready (nbf claim)
 * 	- If using multiple keys, the "kid" claim in JWT header does not match a known key
 */
class JwtValidatorMiddleware implements MiddlewareInterface
{
	public function __construct(
		private Proof $proof,
		private string $header = "Authorization",
		private ?string $scheme = "Bearer"
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$jwt = $this->getJwt(
			$request->getHeaderLine($this->header),
			$this->scheme
		);

		if( $jwt ) {

			try {

				$token = $this->proof->decode($jwt);
			}
			catch( TokenDecodingException $tokenDecodingException ){
				throw new UnauthorizedHttpException(
					authMethod: "Bearer",
					message: "Your token is not valid.",
					previous: $tokenDecodingException
				);
			}
			catch( SignerNotFoundException $signerNotFoundException ){
				throw new UnauthorizedHttpException(
					authMethod: "Bearer",
					message: "Your token is not valid.",
					previous: $signerNotFoundException
				);
			}

			$request = $request->withAttribute(
				Token::class,
				$token
			);
		}

		return $handler->handle($request);
	}

	/**
	 * Extract the JWT (if any) from the header content.
	 *
	 * @param string $contents
	 * @param string|null $scheme
	 * @return string|null
	 */
	private function getJwt(string $contents, ?string $scheme = "Bearer"): ?string
	{
		$b64encoded = "[0-9a-zA-Z_\+\=\-\/]+";

		$pattern = \sprintf(
			"/^%s(%s\.%s\.%s)$/i",
			$scheme ? ($scheme . " ") : "",
			$b64encoded, $b64encoded, $b64encoded
		);

		if( \preg_match($pattern, $contents, $match) ){
			return $match[1];
		}

		return null;
	}
}