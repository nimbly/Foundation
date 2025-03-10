<?php

namespace Nimbly\Foundation\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

#[AsCommand("setup", "Runs initial setup and configuration")]
class Setup extends Command
{
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);

		$ENV = [];
		$CONFIG = [];

		$CONFIG["app"]["name"] = $style->ask("What is the name of your application or service?", \config("app.name"));
		$ENV["TIMEZONE"] = $style->ask("What timezone would you like to use?", \config("app.timezone"));

		$http_service = $style->confirm("Will you be running an HTTP service?");

		if( $http_service ){
			$ENV["HTTP_LISTEN"] = $style->ask("What IP and port number should the HTTP server listen on?", \config("http.server.listen"));
			$ENV["HTTP_MAX_CONNECTIONS"] = $style->ask("What is the maximum number of concurrent connections?", \config("http.server.max_connections"));
			$ENV["HTTP_MAX_REQUEST_SIZE"] = $style->ask("What is the maximum request size in bytes?", \config("http.server.max_request_size"));
			$this->updateEnv($ENV);

			$CONFIG["http"]["default_error_message"] = $style->ask("What user facing error message would you like to use in the case of 5xx responses?", \config("http.default_error_message"));
		}

		$jwt_support = $style->confirm("Will you need JWT support?");

		if( $jwt_support ) {
			$ENV["JWT_SIGNER"] = $style->choice("What signing method would you like to use?", ["keypair", "hmac"], \config("jwt.signer"));

			$jwt_setup = $style->confirm("Would you like to configure your JWT signing method (\"" . $ENV["JWT_SIGNER"] . "\") now?");

			if( $jwt_setup ){
				if( $ENV["JWT_SIGNER"] === "hmac" ){
					$generate_hmac = $style->confirm("Would you like to auto generate an HMAC secret?");

					if( !$generate_hmac ){
						$hmac = $style->askHidden("Input your HMAC secret: ");
					}

					$jwt_hmac_command = new ArrayInput([
						"command" => "jwt:hmac",
						"secret" => $hmac ?? "",
					]);

					$jwt_hmac_command->setInteractive(false);
					$this->getApplication()->doRun($jwt_hmac_command, new NullOutput);
				}
				else {
					$generate_keypair = $style->confirm("Would you like to auto generate a keypair?");

					if( $generate_keypair ){
						$jwt_hmac_command = new ArrayInput([
							"command" => "jwt:keypair",
						]);

						$jwt_hmac_command->setInteractive(false);
						$this->getApplication()->doRun($jwt_hmac_command, $output);
					}
				}

				$CONFIG["jwt"]["issuer"] = $style->ask("Who is the issuer for JWTs?", \config("jwt.issuer") ?: \config("app.name"));

				if( $CONFIG["jwt"]["issuer"] === \config("app.name") ){
					$CONFIG["jwt"]["issuer"] = null;
				}
			}
		}

		foreach( $CONFIG as $file => $values ){
			$this->updateConfig($file, $values);
		}

		$this->updateEnv($ENV);

		return 0;
	}

	/**
	 * Update a configuration file with a new value.
	 *
	 * @param string $file
	 * @param array<string,mixed> $values
	 * @return void
	 */
	private function updateConfig(string $file, array $values): void
	{
		$config_file = \sprintf("%s/config/%s.php", APP_ROOT, $file);

		$config = \file_get_contents($config_file);

		foreach( $values as $key => $value ){
			$search[] = "/\"{$key}\" => \"?.*\"?,\n??$/m";
			$replace[] = \sprintf(
				"\"{$key}\" => %s,",
				match( \gettype($value) ){
					"boolean" => $value ? "true" : "false",
					"integer", "float", "double" => $value,
					"string" => "\"{$value}\"",
					"NULL" => "null",
					default => throw new UnexpectedValueException("Can't handle type.")
				}
			);
		}

		$config = \preg_replace($search, $replace, $config);

		\file_put_contents($config_file, $config);
	}

	/**
	 * Update the environment with new values.
	 *
	 * @param array<string,mixed> $values
	 * @return void
	 */
	private function updateEnv(array $values): void
	{
		$env_file = APP_ROOT . "/.env";

		$env = \file_get_contents($env_file);

		foreach( $values as $key => $value ){
			$search[] = "/^{$key}=.*\n??$/m";
			$replace[] = "{$key}={$value}";
		}

		$env = \preg_replace($search, $replace, $env);

		\file_put_contents($env_file, $env);
	}
}