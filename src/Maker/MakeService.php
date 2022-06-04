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

final class MakeService extends AbstractMaker
{
    public function __construct(private Generator $generator, private string $templatePath)
    {

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
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the service extension class (e.g. <fg=yellow>AppService</>)')
            ->setHelp("Create a new Service class")
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
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
            ['use_statements' => $useStatements]
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
