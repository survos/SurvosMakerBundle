<?php

namespace App\Command;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Type;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

use function Symfony\Component\String\u;

#[AsCommand('survos:make:command', 'Generate a Symfony 7.3 console command')]
final class SurvosMakeCommand
{
    private const TYPES = [Type::String, Type::Int, Type::Bool, Type::Array];

    public function __construct(
        #[Autowire('%kernel.project_dir%/src/Command')] private string $dir,
    )
    {
    }


    public function __invoke(
        SymfonyStyle                                                           $io,
        #[Argument(description: 'command name, e.g. app:do-something')] string $name = '',
        #[Argument(description: 'description')] ?string                        $description = null, // prompt if null
        #[Option(description: 'overwrite the existing file')] bool             $force = true,
        #[Option(description: 'add the project dir to the constructor')] bool  $projectDir = true,
        #[Option(description: 'namespace')] string                             $ns = "App\\Command"
    ): int
    {
        if (!class_exists(PhpNamespace::class)) {
            $io->error("Missing dependency:\n\ncomposer req nette/php-generator");
            return Command::FAILURE;
        }

        $namespace = new PhpNamespace($ns);
        $commandDir = $this->dir;
        if (!file_exists($commandDir)) {
            mkdir($commandDir, 0777, true);
        }
        array_map(fn(string $use) => $namespace->addUse($use), [
            Command::class,
            Option::class,
            Argument::class,
            SymfonyStyle::class,
            AsCommand::class,
            Autowire::class,
        ]);

        if (!$name) {
            $name = $io->ask('command name, e.g. app:do-something');
        }
        $shortName = u($name)->replace('app:', '')->title(true)->replace(':', '')->toString();
        $commandClass = $shortName . "Command";

        if (!$description) {
            $description = $io->ask('one-line command description');
        }

        $class = $namespace->addClass($commandClass);
        $class->addAttribute(AsCommand::class, [
            $name,
            $description,
        ]);
        $method = $class->addMethod('__construct');
        if ($projectDir) {
            $parameter = $method->addPromotedParameter('projectDir', null);
            $parameter->setVisibility('private');
            $parameter->setType(Type::String);
            $parameter->addAttribute(Autowire::class, ['%kernel.project_dir%/']);
        }

        $fields = [];
        $args = [];
        $method = $class->addMethod('__invoke');
        $method->setReturnType('int');
        $parameter = $method->addParameter('io')->setType(SymfonyStyle::class);
        $hasOptional = false;
        $body = '';
        if ($projectDir) {
            $body .=  '$io->writeln("The project directory is " . $this->projectDir);' . "\n\n";
        }

        while ($argument = $this->askForNextArgument($io, $method)) {
            $body .= $this->addBodyBoilerplate($argument, "Argument");
            $method->addBody($body);
            $io->writeln((string)$method);
        }

        while ($option = $this->askForNextOption($io, $method)) {
            $body .= $this->addBodyBoilerplate($option, "Option");
            $method->addBody($body);
            $io->writeln((string)$method);
        }

        $body .= "return Command::SUCCESS;";
        $method->setBody($body);
        $filename = $this->dir . '/' . $commandClass . '.php';
        $io->writeln((string)$namespace);
        file_put_contents($filename, '<?php' . "\n\n" . $namespace);
        $io->success(self::class . ' success. ' . $filename);

        return Command::SUCCESS;
    }

    private function askForNextArgument(
        SymfonyStyle $io,
        Method       $method,
    ): ?string
    {
        $io->writeln('');
        $questionText = 'Enter the argument name (or press <return> to stop adding arguments)';

        if (
            !$fieldName = $io->ask($questionText, null, function ($name) use ($method) {
                if ($name && $method->hasParameter($name)) {
                    throw new \InvalidArgumentException(sprintf('The "%s" argument already exists.', $name));
                }
                return $name;
            })
        ) {
            return null;
        };

        $parameter = $method->addParameter($fieldName);

        if ($default = $io->ask('Enter default value (blank for none)')) {
            $parameter->setDefaultValue($default);
        }
        if ($fieldType = $this->askType($io, 'Enter argument type (eg. <fg=yellow>string</> by default)')) {
            $parameter->setType($fieldType);
        }

        $attributeOptions = [];
        if ($description = $io->ask('Argument description (blank for none)')) {
            $attributeOptions['description'] = $description;
        }
        $parameter->addAttribute(Argument::class, $attributeOptions);
        return $fieldName;
    }

    public function askType(SymfonyStyle $io, string $message): string
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

    private function getDefault(string $fieldType, mixed $default = null)
    {
        return match ($fieldType) {
            'string' => sprintf("'%s'", $default),
            'bool' => $default ? 'true' : 'false',
            'int' => $default,
            default => null, // str_starts_with('?', $fieldType) ? null: dd($fieldType)
        };
    }

    /**
     * @param string $option
     * @return string
     */
    public function addBodyBoilerplate(string $option, string $text): string
    {
        return sprintf(<<<'PHP'
if ($%s) {
    $io->writeln("%s %s: $%s");
}

PHP, $option, $text, $option, $option);
    }

    private function askForNextParameter(SymfonyStyle $io, Method $method, string $questionText): ?string
    {
        $fieldName = $io->ask($questionText . ' (or press <return> to stop)', null, function (?string $name) use ($method) {
            if ($name && $method->hasParameter($name)) {
                throw new \InvalidArgumentException(sprintf('The "%s" argument or option already exists.', $name));
            }
            return $name;
        });
        return $fieldName;
    }

    private function askForNextOption(
        SymfonyStyle $io,
        Method       $method,
    ): ?string
    {
        if (!$fieldName = $this->askForNextParameter($io, $method, 'Enter the option name')) {
            return null;
        }
        $parameter = $method->addParameter($fieldName);

        if ($fieldType = $this->askType($io, 'Enter option type (eg. <fg=yellow>string</> by default)')) {
            $parameter->setType($fieldType);
        }
        $default = $this->getDefault($fieldType);
        $default = $io->ask('Enter default value (required)', $default, function (?string $default) {
//            if ($default === 'null') {
//                $default = null;
//            }
        });
        if ($default === 'null') {
            $default = null;
        }

        $parameter->setDefaultValue($default);
        $argumentAttributeValues = [];
        if ($description = $io->ask('Option description (blank for none)')) {
            $argumentAttributeValues['description'] = $description;
        }
        if ($shortCut = $io->ask('Enter shortcut for the option (blank for none)')) {
            $argumentAttributeValues['shortcut'] = $shortCut;
        }

        $parameter->addAttribute(Option::class, $argumentAttributeValues);
        return $fieldName;
    }
}
