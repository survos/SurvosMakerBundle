<?php

namespace Survos\Bundle\MakerBundle\Maker;

use Knp\Menu\ItemInterface;
use LogicException;
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
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
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

use function PHPUnit\Framework\isNull;
use function Symfony\Component\String\u;

final class MakeInvokableCommand extends AbstractMaker implements MakerInterface
{
    private const TYPES = ['string', '?string', 'int', '?int', 'bool', '?bool', 'array', '?array'];

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
            //            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeCommand.txt'))
            ->addArgument('name', InputArgument::REQUIRED, sprintf('Choose a command name (e.g. <fg=yellow>app:%s</>)', Str::asCommand(Str::getRandomTerm())))
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite if it already exists.')
            ->addOption('prefix', null, InputOption::VALUE_OPTIONAL, 'Prefix the command name, but not the generated class, e.g. survos:make:user, app:do:something')
            ->addOption('inject', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Interfaces to inject, e.g. EntityManagerInterface', [])
            ->addOption('option', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'e.g. bool:override=false')

//            ->addOption('arg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'string arguments')
//            ->addOption('int-arg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'int arguments')
//            ->addOption('bool-arg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'bool arguments')
//
//            ->addOption('oarg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'optional string arguments')
//            ->addOption('oint-arg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'optional int arguments')
//            ->addOption('obool-arg', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'optional bool arguments')
        ;
//            ->addOption('inject', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Interfaces to inject, e.g. EntityManagerInterface', []);
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
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

        $description = $io->ask('A brief description of what the command does (blank for none)', '');

        // walk through the different command line arguments/options, by type, to pass to the template
        $hasOptional = false;
        $isFirstField = true;
        $fields = [];
        $args = [];
        while (true) {
            [$argName, $argParams] = $this->askForNextArgument($io, $fields, $isFirstField);
            $isFirstField = false;

            if ($argName === null) {
                break;
            }

            if (str_starts_with($argParams['phpType'], '?')) {
                $hasOptional = true;
                $optionalArgument = $argName;
            } else {
                if ($hasOptional) {
                    throw new LogicException("Required argument $argName cannot come after optional argument $optionalArgument");
                }
            }

            $args[$argName] = $argParams;
        }

        $isFirstField = true;
        $options = [];
        while (true) {
            [$optionName, $optionParams] = $this->askForNextOption($io, $fields, $isFirstField);
            $isFirstField = false;

            if ($optionName === null) {
                break;
            }

            $options[$optionName] = $optionParams;
        }

        $generatedFilename = $this->generator->generateClass(
            $classNameDetails->getFullName(),
            __DIR__ . '/../../templates/skeleton/Menu/InvokableCommand.tpl.twig',
            $v = [
                'commandName' => $commandName,
                'commandDescription' => $description,
                'args' => $args,
                'options' => $options,
                'entity_full_class_name' => $classNameDetails->getFullName(),
                'use_statements' => $useStatements,
            ]
        );

        // --force flag
        if ($input->getOption('force')) {
            unlink($generatedFilename);
        }

        $generator->writeChanges();
        print file_get_contents($generatedFilename);
        //        dump($v);

        $this->writeSuccessMessage($io);

        $io->success('Run your command with ' . $commandName);

        $io->text([
            sprintf('Next: Open %s and customize it', $generatedFilename),
        ]);
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement @method string getCommandDescription()
    }

    private function askForNextArgument(
        ConsoleStyle $io,
        array &$fields,
        bool $isFirstField
    ): array|null {
        $io->writeln('');

        if ($isFirstField) {
            $questionText = 'New argument for the command (press <return> to stop adding arguments)';
        } else {
            $questionText = 'Add another argument? Enter the argument name (or press <return> to stop adding arguments)';
        }

        $fieldName = $io->ask($questionText, null, function ($name) use ($fields) {
            // allow it to be empty
            if (!$name) {
                return $name;
            }

            if (\in_array($name, $fields)) {
                throw new \InvalidArgumentException(sprintf('The "%s" argument already exists.', $name));
            }

            return $name;
        });

        if (!$fieldName) {
            return null;
        }

        $fieldType = $this->askType($io, 'Enter argument type (eg. <fg=yellow>string</> by default)');
        $default = $io->ask('Enter default value (blank for none)');
        $description = $io->ask('Argument description (blank for none)');

        $fields[] = $fieldName;

        return [$fieldName, [
            'phpType' => $fieldType,
            'default' => $default,
            'description' => $description ?? "($fieldType)",
        ]];
    }

    private function askForNextOption(
        ConsoleStyle $io,
        array &$fields,
        bool $isFirstField
    ): array|null {
        $io->writeln('');

        if ($isFirstField) {
            $questionText = 'New option for the command (press <return> to stop adding options)';
        } else {
            $questionText = 'Add another option? Enter the option name (or press <return> to stop adding options)';
        }

        $fieldName = $io->ask($questionText, null, function ($name) use ($fields) {
            // allow it to be empty
            if (!$name) {
                return $name;
            }

            if (\in_array($name, $fields)) {
                throw new \InvalidArgumentException(sprintf('The "%s" argument or option already exists.', $name));
            }

            return $name;
        });

        if (!$fieldName) {
            return null;
        }

        $fieldType = $this->askType($io, 'Enter option type (eg. <fg=yellow>string</> by default)');
        $default = $io->ask('Enter default value (blank for none)');
        $shortCut = $io->ask('Enter shortcut for the option (blank for none)');
        $description = $io->ask('Argument description (blank for none)');

        $fields[] = $fieldName;

        return [$fieldName, [
            'phpType' => $fieldType,
            'default' => $default,
            'shortCut' => $shortCut,
            'description' => $description ?? "($fieldType)",
        ]];
    }

    /**
     * Ask for valid existing type
     *
     * @param ConsoleStyle $io
     * @param string $message
     * @return string
     */
    public function askType(ConsoleStyle $io, string $message): string
    {
        $type = null;
        while (null === $type) {
            $question = new Question($message, 'string');
            $question->setAutocompleterValues(self::TYPES);
            $type = $io->askQuestion($question);

            if ('?' === $type) {
                $io->note('Allowed types: ' . implode(',', self::TYPES));
                $io->writeln('');

                $type = null;
            } elseif (!\in_array($type, self::TYPES)) {
                $io->note('Allowed types: ' . implode(',', self::TYPES));
                $io->error(sprintf('Invalid type "%s".', $type));
                $io->writeln('');

                $type = null;
            }
        }

        return $type;
    }
}
