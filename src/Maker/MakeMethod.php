<?php

namespace Survos\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;

final class MakeMethod extends AbstractMaker
{
    public function __construct(
        private Generator $generator,
        private string $templatePath
    ) {
    }

    public static function getCommandName(): string
    {
        return 'survos:no-input-stream-make:method';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new method';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('className', InputArgument::REQUIRED, 'The name of the existing class (e.g. <fg=yellow>App/Service/MathService</> or FQCN)')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the method (e.g. <fg=yellow>calculateSum</>)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'overwrite this method if it already exists')
            ->addOption('body', 'b', InputOption::VALUE_NONE, 'only replace the body of an existing method, requires --force')
            ->setHelp("Create or update a method in a class")
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {

            // If testing this will get input added by `CommandTester::setInputs` method.
            $inputSteam = ($input instanceof StreamableInputInterface) ? $input->getStream() : null;
            $content = $inputSteam ? stream_get_contents($inputSteam) : null;
//            dd($content);
//        if ($input->getOption('no-interaction')) {
//        }

        $reflectionClass = new \ReflectionClass($className = $input->getArgument('className'));

        // first, see if the method already exists.
        if ($reflectionClass->hasMethod($methodName = $input->getArgument('name')))
        {
            if (!$input->getOption('force')) {
                $io->error("Method $methodName already exists in $className, use --force to overwrite it");
            } else {
                $reflectionMethod = $reflectionClass->getMethod($methodName);
                $source = file_get_contents($reflectionMethod->getDeclaringClass()->getFileName());
                $sourceLines = explode("\n", $source);
                $source = join("\n", array_splice($sourceLines, $reflectionMethod->getStartLine()-1, $reflectionMethod->getEndLine() - $reflectionMethod->getStartLine()));
                dd($source, $content);
            }
        }




        dd($reflectionClass->getFileName(), $content);


        $extensionClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Service\\',
            'Service'
        );

        $useStatements = new UseStatementGenerator([
        ]);

        $generator->generateClass(
            $extensionClassNameDetails->getFullName(),
            $this->templatePath . 'Service/Service.tpl.php',
            [
                'content' => $content,
                'use_statements' => $useStatements,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new extension class and start customizing it.',
            'Find the documentation at <fg=yellow>http://symfony.com/doc/current/templating/twig_extension.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        //        $dependencies->addClassDependency(
        //            AbstractExtension::class,
        //            'twig'
        //        );
    }
}
