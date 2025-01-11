<?php

namespace Nimbly\Foundation\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand("jwt:keypair", "Generate a new key pair for signing JWTs.")]
class JwtKeypair extends Command
{
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);

		if( !\file_exists(".env") ){
			$environment = "JWT_PRIVATE_KEY=\nJWT_PUBLIC_KEY=\n";
		}
		else {

			$environment = \file_get_contents(".env");

			if( \preg_match("/^JWT_PRIVATE_KEY=(.*)\n?$/m", $environment, $match) ){
				if( !empty($match[1]) ){
					$style->warning("You already have a private key defined. By overwriting this secret, any currently signed in-flight JWTs will be invalid.");
					$confirmed = $style->confirm("Are you sure you want to continue?", false);

					if( !$confirmed ){
						$style->info("Aborted");
						return -1;
					}
				}
			}
			else {
				$environment .= ("\n\n" . "JWT_PRIVATE_KEY=" . "\n");
			}
		}

		$keypair = \openssl_pkey_new([
			"private_key_bits" => 4096,
			"private_key_type" => OPENSSL_KEYTYPE_RSA
		]);

		$public_key = \openssl_pkey_get_details($keypair)["key"];

		\openssl_pkey_export($keypair, $private_key);

		if( !\file_exists(APP_ROOT . "/keys") ){
			\mkdir(APP_ROOT . "/keys");
		}

		\file_put_contents(APP_ROOT . "/keys/private.pem", $private_key);
		\file_put_contents(APP_ROOT . "/keys/public.pem", $public_key);

		$environment = \preg_replace(
			[
				"/^JWT_PRIVATE_KEY=.*\n?$/m",
				"/^JWT_PUBLIC_KEY=.*\n?$/m"],
			[
				"JWT_PRIVATE_KEY=" . \trim(\base64_encode($private_key), "=\n"),
				"JWT_PUBLIC_KEY=" . \trim(\base64_encode($public_key), "=\n") . "\n",
			],
			$environment
		);

		\file_put_contents(".env", $environment);

		$style->success("Success");

		return 0;
	}
}