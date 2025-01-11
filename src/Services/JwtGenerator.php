<?php

namespace Nimbly\Foundation\Services;

use DateInterval;
use DateTime;
use Nimbly\Proof\Proof;
use Nimbly\Proof\Token;
use Ramsey\Uuid\Uuid;

class JwtGenerator
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
	 * @param string $subject The subject of the JWT (eg, user ID, account ID, etc.)
	 * @param DateInterval $ttl The TTL of the JWT, used to compute the expiration timestamp.
	 * @param array<string,mixed> $claims Additional claims to include.
	 * @return string The signed JWT string.
	 */
	public function createJwt(
		string $subject,
		DateInterval $ttl,
		array $claims = []): string
	{
		return $this->proof->encode(
			$this->createToken($subject, $ttl, $claims)
		);
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
			...$claims,
			"exp" => (int) (new DateTime)->add($ttl)->format("U"),
		]);
	}

	/**
	 * Decode a signed JWT into a Token instance.
	 *
	 * @param string $jwt A signed JWT.
	 * @return Token
	 */
	public function decodeJwtToToken(string $jwt): Token
	{
		return $this->proof->decode($jwt);
	}
}