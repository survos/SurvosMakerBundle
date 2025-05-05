<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Type;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

use function Symfony\Component\String\u;

#[AsCommand('survos:make:command', 'Generate a Symfony 7.3 console command')]
final class SurvosMakeCommand
{
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
        $shortName = u($name)->replace('app:', '')->title(true)->replace(':', '')->toString();
        $commandClass = $shortName . "Command";
        array_map(fn(string $use) => $namespace->addUse($use), [
            Command::class,
            Option::class,
            Argument::class,
            SymfonyStyle::class,
            AsCommand::class,
            Autowire::class,
        ]);

        if (!$description) {
            $description = $io->ask('one-line command description');
        }

        $class = $namespace->addClass($commandClass);
        $class->addAttribute(AsCommand::class, [
                $name,
                $description,
            ]
        );
        $method = $class->addMethod('__construct');
        if ($projectDir) {
            $parameter = $method->addParameter('projectDir', null);
            $parameter->setType('string');
            $parameter->addAttribute(Autowire::class, ['%kernel.project_dir%']);
        }

        $method = $class->addMethod('__invoke');
        $method->setReturnType('int');
        $method->setBody(<<<'PHP'
$io->writeln($this->projectDir);
return Command::SUCCESS;
PHP
        );
        $filename = $this->dir . '/' . $commandClass . '.php';
        $io->writeln((string)$namespace);
        file_put_contents($filename, '<?php' . "\n\n" . $namespace);
        $io->success(self::class . ' success. ' . $filename);


        return Command::SUCCESS;
    }
}
