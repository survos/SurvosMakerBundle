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

final class MakeModel extends AbstractMaker
{
    public function __construct(
        private Generator $generator,
        private string $templatePath
    ) {
    }

    public static function getCommandName(): string
    {
        return 'survos:make:model';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new model class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the model  class (e.g. <fg=yellow>Book</>)')
            ->setHelp("Create a new Model class")
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $extensionClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            'Model\\',
            ''
        );

        $useStatements = new UseStatementGenerator([
        ]);

        $generator->generateClass(
            $extensionClassNameDetails->getFullName(),
            $this->templatePath . 'Model/Model.tpl.php',
            [
                'use_statements' => $useStatements,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new model class and start customizing it.',
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
