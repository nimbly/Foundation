<?php

namespace Nimbly\Foundation\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand("jwt:hmac", "Generate a new HMAC secret for signing JWTs.")]
class JwtHmac extends Command
{
	protected function configure(): void
    {
        $this->addArgument("secret", InputArgument::OPTIONAL, "The HMAC secret");
		$this->addOption("force", "f", InputOption::VALUE_NONE, "Overwrite existing HMAC secret");
    }

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);

		if( !\file_exists(".env") ){
			$environment = "JWT_HMAC_SECRET=";
		}
		else {

			$environment = \file_get_contents(".env");

			if( \preg_match("/^JWT_HMAC_SECRET=(.*)\n?$/m", $environment, $match) ){
				if( !empty($match[1]) ){
					$style->warning("You already have a JWT HMAC secret defined. By overwriting this secret, any currently signed in-flight JWTs will be invalid.");
					$confirmed = $style->confirm("Are you sure you want to continue?", false);

					if( !$confirmed ){
						$style->info("Aborted");
						return -1;
					}
				}
			}
			else {
				$environment .= ("\n\n" . "JWT_HMAC_SECRET=" . "\n");
			}
		}

		$hmac = $input->getArgument("secret");

		if( empty($hmac) ){
			$generator = new ComputerPasswordGenerator;
			$generator->setOptionValue(ComputerPasswordGenerator::OPTION_LENGTH, 24)
			->setOptionValue(ComputerPasswordGenerator::OPTION_UPPER_CASE, true)
			->setOptionValue(ComputerPasswordGenerator::OPTION_LOWER_CASE, true)
			->setOptionValue(ComputerPasswordGenerator::OPTION_NUMBERS, true)
			->setOptionValue(ComputerPasswordGenerator::OPTION_SYMBOLS, true);

			$hmac = $generator->generatePassword();
		}

		$environment = \preg_replace(
			"/^JWT_HMAC_SECRET=.*\n?$/m",
			"JWT_HMAC_SECRET=" . \trim(\base64_encode($hmac), "=\n"),
			$environment
		);

		\file_put_contents(".env", $environment);

		$style->success("Success");

		return 0;
	}
}