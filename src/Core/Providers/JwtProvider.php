<?php

namespace Nimbly\Foundation\Core\Providers;

use Nimbly\Carton\Container;
use Nimbly\Carton\ServiceProviderInterface;
use Nimbly\Foundation\Services\JwtGenerator;
use Nimbly\Proof\Proof;

class JwtProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->singleton(
			JwtGenerator::class,
			function(Container $container): JwtGenerator {
				return new JwtGenerator(
					$container->get(Proof::class),
					\config("jwt.issuer") ?: \config("app.name"),
				);
			}
		);
	}
}