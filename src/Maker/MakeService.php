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

final class MakeService extends AbstractMaker
{
    public function __construct(
        private Generator $generator,
        private string $templatePath
    ) {
    }

    public static function getCommandName(): string
    {
        return 'survos:make:service';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new service';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the service class (e.g. <fg=yellow>AppService</>)')
//            ->addOption('force', InputOption::VALUE_NONE|InputOption::VALUE_NEGATABLE)
//            ->addArgument('methodContent', InputArgument::REQUIRED, 'PHP Code')
            ->setHelp("Create a new Service class")
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $inputSteam = ($input instanceof StreamableInputInterface) ? $input->getStream() : null;
        if ($inputSteam) {
            $contents = stream_get_contents($inputSteam);
            dd($contents);
        } else {
            $contents = null;
        }
        // If nothing from input stream use STDIN instead.
        $inputSteam = $inputSteam ?? STDIN;

        // If testing this will get input added by `CommandTester::setInputs` method.
        //        dd($input::class);
        //        $x = stream_get_contents($input->getStream());
        //            $inputSteam = ($input instanceof StreamableInputInterface) ? $input->getStream() : null;
        //            $content = $inputSteam ? stream_get_contents($inputSteam) : null;
        //            dd($content);
        //        if ($input->getOption('no-interaction')) {
        //        }



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
                'content' => $contents,
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
