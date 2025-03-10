<?php

namespace Nimbly\Foundation\Http\Providers;

use Nimbly\Carton\Container;
use Nimbly\Carton\ServiceProviderInterface;
use Nimbly\Proof\Proof;
use Nimbly\Proof\Signer\HmacSigner;
use Nimbly\Proof\Signer\KeypairSigner;
use UnexpectedValueException;

/**
 * Provides the Proof instance necessary to create and/or validate
 * your JWT tokens.
 *
 * @see `config/jwt.php` for configuration options.
 */
class JwtProvider implements ServiceProviderInterface
{
	/**
	 * @inheritDoc
	 */
	public function register(Container $container): void
	{
		$container->singleton(
			Proof::class,
			function(): Proof {

				$adapter = \config("jwt.signer");

				$signer = match($adapter) {
					"hmac" => new HmacSigner(
						\config("jwt.algorithm"),
						\base64_decode(\getenv("JWT_HMAC_SECRET"))
					),

					"keypair" => new KeypairSigner(
						\config("jwt.algorithm"),
						\getenv("JWT_PUBLIC_KEY") ? \openssl_get_publickey(\base64_decode(\getenv("JWT_PUBLIC_KEY"))) : null,
						\getenv("JWT_PRIVATE_KEY") ? \openssl_get_privatekey(\base64_decode(\getenv("JWT_PRIVATE_KEY"))) : null,
					),

					default => throw new UnexpectedValueException(
						\sprintf("\"%s\" is not a valid JWT signer option.", $adapter)
					)
				};

				return new Proof(
					signer: $signer,
					leeway: \config("jwt.leeway") ?? 0,
					keyMap: \config("jwt.keymap") ?? []
				);
			}
		);
	}
}