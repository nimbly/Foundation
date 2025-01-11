<?php

namespace Nimbly\Foundation\Commands;

use Psy\Configuration;
use Psy\Shell as PsyShell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand("shell", "Starts a REPL shell", ["sh"])]
class Shell extends Command
{
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$shell = new PsyShell(
			new Configuration([
				"startupMessage" => "With great power comes great responsibility.",
			])
		);

		$shell->run();

		return 0;
	}
}