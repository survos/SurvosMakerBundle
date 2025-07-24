<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand('app:run', 'Run away!')]
class RunCommand
{
	public function __construct(
		#[Autowire('%kernel.project_dir%/')]
		private ?string $projectDir = null,
	) {
	}


	public function __invoke(
		SymfonyStyle $io,
		#[Argument]
		string $x,
	): int
	{
		if ($x) {
		    $io->writeln("Option x $x");
		}$io->writeln($this->projectDir);
		return Command::SUCCESS;
	}
}
