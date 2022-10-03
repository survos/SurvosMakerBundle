<?php

namespace Survos\Bundle\MakerBundle\Maker;

use Knp\Menu\ItemInterface;
use Survos\BootstrapBundle\Event\KnpMenuEvent;
use Survos\BootstrapBundle\Menu\MenuBuilder;
use Survos\BootstrapBundle\Traits\KnpMenuHelperInterface;
use Survos\BootstrapBundle\Traits\KnpMenuHelperTrait;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\ConfigureWithAttributes;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;

use function Symfony\Component\String\u;

final class MakeInvokableCommand extends AbstractMaker implements MakerInterface
{
    public function __construct(
        private Generator $generator,
        private string $templatePath
    ) {
    }

    public static function getCommandDescription(): string
    {
        return 'Generate an invokable command';
    }

    public static function getCommandName(): string
    {
        return 'survos:make:command';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('name', InputArgument::REQUIRED, sprintf('Choose a command name (e.g. <fg=yellow>app:%s</>)', Str::asCommand(Str::getRandomTerm())))
            ->addArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'space-delimited arguments')

//            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeCommand.txt'))
            ->addOption('description', 'desc', InputOption::VALUE_OPTIONAL, sprintf('A brief description of what the command does'))
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite if it already exists.')
            ->addOption('prefix', null, InputOption::VALUE_OPTIONAL, 'Prefix the command name, but not the generated class, e.g. survos:make:user, app:do:something')
            ->addOption('inject', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Interfaces to inject, e.g. EntityManagerInterface', [])
            ->addOption('option', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'string arguments')

            ->addOption('arg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'string arguments')
            ->addOption('int-arg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'int arguments')
            ->addOption('bool-arg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'bool arguments')

            ->addOption('oarg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'optional string arguments')
            ->addOption('oint-arg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'optional int arguments')
            ->addOption('obool-arg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'optional bool arguments')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $commandName = $input->getArgument('name');
        if ($prefix = $input->getOption('prefix')) {
            $commandName = $prefix . ':' . $commandName;
        }
        $io->success('Run your command with ' . $commandName);

        //        return Command::SUCCESS;
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            InvokableServiceCommand::class,
            ConfigureWithAttributes::class,
            RunsCommands::class,
            RunsProcesses::class
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $commandName = $input->getArgument('name'); // create:user or app:create-user
        $shortName = u($commandName)->camel()->title(); // 'CreateUSer'
        $classNameDetails = $generator->createClassNameDetails(
            $shortName,
            'Command\\',
            'Command'
        );

        $useStatements = new UseStatementGenerator([
            AsCommand::class,
            InvokableServiceCommand::class,
            ConfigureWithAttributes::class,
            RunsCommands::class,
            RunsProcesses::class,
            Argument::class,
            Option::class,
            IO::class,
        ]);

        // bin/console survos:make:command app:testx x ?y int:z ?int:a:"A description of argument a" int:numberOfTimes-repeat?:"Option a"
        //  --description="Just a silly test"
        /*
         *
         *
            name: string $name
            ?name: ?string $name
            ?int:name: ?int $name
        name[]: array $name
        */

        // walk through the different command line arguments/options, by type, to pass to the template
        $args = [];
        $options = [];

        $commandArgs = $input->getArgument('args');
        dump($commandArgs);
        $hasOptional = false;
        foreach ($commandArgs as $argString) {
            $description = null;
            $default = null;

            $argTokens = explode(':', $argString);
            $argTokenCount = count($argTokens);
            if ($argTokenCount === 3) {
                [$argType, $argName, $description] = $argTokens;
            } elseif ($argTokenCount === 2) {
                [$argType, $argName] = $argTokens;
            } else {
                $argName = $argString;
                if (str_starts_with($argName, '?')) {
                    $argType = '?string';
                    $argName = str_replace('?', '', $argName);
                } else {
                    $argType = 'string';
                }
            }

            if (empty($description)) {
                $description = "($argType)";
            }

            if (str_ends_with($argName, '?')) {
                $argName = trim($argName, '?');

                // shortcut is after the name, as a hypen
                if (str_contains($argName, '-')) {
                    [$argName, $shortcut] = explode('-', $argName, 2);
                } else {
                    $shortcut = null;
                }

                if ($default) {
                }
                $options[$argName] = [
                    'default' => $default,
                    'phpType' => $argType,
                    'shortCut' => $shortcut,
                    'description' => $description,
                ];
            } else {
                if (str_starts_with($argType, '?')) {
                    $hasOptional = true;
                    $optionalArgument = $argName;
                } else {
                    if ($hasOptional) {
                        throw new \LogicException("required argument $argName cannot come after optional argument $optionalArgument");
                    }
                }

                $args[$argName] = [
                    'phpType' => $argType,
                    'default' => $default,
                    'description' => $description,
                ];
            }
        }
        //        $args = [];
        //        foreach (['arg' => 'string', 'int-arg' => 'int', 'bool-arg' => 'bool'] as $argName=>$argType) {
        //            $commandArguments = $input->getOption($argName);
        //            foreach ( $input->getOption($argName) as $commandArg) {
        //                $args[$commandArg] = $argType;
        //            }
        //        }
        //        dd($args, $options);
        //        // optional arguments must come AFTER the requirement arguments
        //        foreach (['oarg' => '?string', 'oint-arg' => '?int', 'obool-arg' => '?bool'] as $argName=>$argType) {
        //            $commandArguments = $input->getOption($argName);
        //            foreach ( $input->getOption($argName) as $commandArg) {
        //                $args[$commandArg] = $argType;
        //            }
        //        }



        $generatedFilename = $this->generator->generateClass(
            $classNameDetails->getFullName(),
            __DIR__ . '/../../templates/skeleton/Menu/InvokableCommand.tpl.twig',
            $v = [
                'commandName' => $commandName,
                'commandDescription' => $input->getOption('description'),
                'args' => $args,
                'options' => $options,
                'entity_full_class_name' => $classNameDetails->getFullName(),
                'use_statements' => $useStatements,
            ]
        );

        //        unlink($generatedFilename); // we need a --force flag
        $generator->writeChanges();
        print file_get_contents($generatedFilename);
        //        dump($v);

        $this->writeSuccessMessage($io);

        $io->text([
            sprintf('Next: Open %s and customize it', $generatedFilename),
        ]);
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement @method string getCommandDescription()
    }
}
