<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;

#[AsCommand('app:test', 'abc')]
final class AppTestCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __invoke(
        IO $io,
    ): int {
        $io->success($this->getName().' success.');

        return self::SUCCESS;
    }
}
