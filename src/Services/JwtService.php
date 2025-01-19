<?php

namespace Nimbly\Foundation\Services;

use DateTime;
use DateInterval;
use Ramsey\Uuid\Uuid;
use Nimbly\Proof\Proof;
use Nimbly\Proof\Token;
use Nimbly\Proof\ExpiredTokenException;
use Nimbly\Proof\InvalidTokenException;
use Nimbly\Proof\TokenEncodingException;
use Nimbly\Proof\TokenNotReadyException;
use Nimbly\Proof\SignatureMismatchException;

class JwtService
{
	public function __construct(
		protected Proof $proof,
		protected string $issuer
	)
	{
	}

	/**
	 * Generate a JWT.
	 *
	 * This method automatically creates a `jti` (JWT ID in UUID format), `sub` (subject), `iss` (issuer), and `exp` (expiration) claim. Additional claims may be added in the `claims` parameter.
	 *
	 * @param string $subject The subject of the JWT (eg, user ID, account ID, etc.)
	 * @param DateInterval $ttl The TTL of the JWT, used to compute the expiration timestamp.
	 * @param array<string,mixed> $claims Additional claims to include.
	 * @throws TokenEncodingException
	 * @return string The signed JWT string.
	 */
	public function createJwt(
		string $subject,
		DateInterval $ttl,
		array $claims = [],
		?string $kid = null): string
	{
		$token = $this->createToken($subject, $ttl, $claims);

		return $this->proof->encode($token, $kid);
	}

	/**
	 * Create a Token instance.
	 *
	 * @param string $subject The subject of the JWT (eg, user ID, account ID, etc.)
	 * @param DateInterval $ttl The TTL of the JWT, used to compute the expiration timestamp.
	 * @param array<string,mixed> $claims Additional claims to include.
	 * @return Token
	 */
	public function createToken(
		string $subject,
		DateInterval $ttl,
		array $claims = []): Token
	{
		return new Token([
			"jti" => Uuid::uuid4()->toString(),
			"iss" => $this->issuer,
			"sub" => $subject,
			"exp" => (int) (new DateTime)->add($ttl)->format("U"),
			...$claims,
		]);
	}

	public function encodeTokenToJwt(Token $token): string
	{
		return $this->proof->encode($token);
	}

	/**
	 * Decode a signed JWT into a Token instance.
	 *
	 * This will also validate the token, checking the signature,
	 * expiration date, etc. If the token cannot be validated, an
	 * exception will be thrown.
	 *
	 * @param string $jwt A signed JWT.
	 * @throws InvalidTokenException
	 * @throws SignatureMismatchException
	 * @throws ExpiredTokenException
	 * @throws TokenNotReadyException
	 * @return Token
	 */
	public function decodeJwtToToken(string $jwt): Token
	{
		return $this->proof->decode($jwt);
	}
}