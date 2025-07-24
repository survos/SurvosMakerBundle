<?php

namespace App\Command;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand('app:xx', 'A silly test')]
class XxCommand
{
    public function __construct(
        private TaskRepository $taskRepository,
        #[Autowire('%kernel.project_dir%/')]
        private ?string $projectDir = null,
    )
    {
    }


    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'person to greet')]
        string       $name,
        #[Option(description: 'allcaps if shouting', shortcut: 's')]
        bool        $shout = false,
    ): int
    {
        $io->section("Debug information");
        $task = new Task();

        $io->writeln(sprintf("Hello: %s", $shout ? strtoupper($name) : $name));
        return Command::SUCCESS;
    }
}
