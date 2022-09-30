<?php

namespace Survos\Bundle\MakerBundle\Command;

use App\Service\LocationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Twig\Environment;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\ConfigureWithAttributes;
use Zenstruck\Console\IO;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;

#[AsCommand('make:method', 'insert or update a method in an existing class')]
final class MakeMethodCommand extends InvokableServiceCommand
{
    use ConfigureWithAttributes, RunsCommands, RunsProcesses;

    public function __invoke(
        IO              $io,

        #[Argument(description: 'name of the method (to check for existence)')]
        string          $name,
        #[Argument(description: 'overwrite if exists')]
        ?bool           $force,

    ): void
    {

        // this is only valid is are no prompts.
        $input = $io->input();
        // If testing this will get input added by `CommandTester::setInputs` method.
        $inputSteam = ($input instanceof StreamableInputInterface) ? $input->getStream() : null;
        $content = $inputSteam ? stream_get_contents($inputSteam) : null;

        // If nothing from input stream use STDIN instead.
        $inputSteam = $inputSteam ?? STDIN;

        $input = stream_get_contents($inputSteam);
        dd($input);
        $output->write($input);


        $io->note(sprintf("string %s %s", 'name', $name) ?? 'null');
        $io->note(sprintf("string %s %s", 'code', $code) ?? 'null');
        $io->note(sprintf("?int %s %s", 'size', $size) ?? 'null');
        $io->note(sprintf("?bool %s %s", 'force', $force) ?? 'null');

// $this->runCommand('another:command');
// $this->runProcess('/some/script');

        $io->success('test success.');
    }

}
