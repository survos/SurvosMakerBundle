<?php

namespace Survos\Bundle\MakerBundle\Maker;

use Knp\Menu\ItemInterface;
use Survos\BootstrapBundle\Traits\KnpMenuHelperTrait;
use Survos\BootstrapBundle\Event\KnpMenuEvent;
use Survos\BootstrapBundle\Menu\MenuBuilder;
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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;

final class MakeMenu extends AbstractMaker implements MakerInterface
{
    public function __construct(private Generator $generator, private string $templatePath)
    {
    }

    public static function getCommandName(): string
    {
        return 'survos:make:menu';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('menuClassName', InputArgument::OPTIONAL, 'Menu Class Name', '')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite if it already exists.')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {

        $io->success('Add knp_menu_render() to a twig file to render.');

        return Command::SUCCESS;
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            AbstractExtension::class,
            'twig'
        );

        // TODO: Implement configureDependencies() method.
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {

        $shortName = $input->getArgument('menuClassName');
        $classNameDetails = $generator->createClassNameDetails(
            $shortName,
            'EventSubscriber\\',
            'MenuSubscriber'
        );

        $useStatements = new UseStatementGenerator([
            EventSubscriberInterface::class,
            KnpMenuHelperTrait::class,
            ItemInterface::class,
            MenuBuilder::class,
            KnpMenuEvent::class,
            AuthorizationCheckerInterface::class,
        ]);

        $generatedFilename = $this->generator->generateClass(
            $classNameDetails->getFullName(),
            __DIR__ . '/../../templates/skeleton/Menu/MenuEventSubscriber.tpl.twig',
            $v = [
                'entity_full_class_name' => $classNameDetails->getFullName(),
                'use_statements' => $useStatements,
            //                'entity_class_name' => $boundClassDetails ? $boundClassDetails->getShortName() : null,
            //                'form_fields' => $fields,
            //                'entity_var_name' => $entityVarSingular,
            //                'entity_unique_name' => $entityVarSingular . 'Id',
            //                'field_type_use_statements' => $mergedTypeUseStatements,
            //                'constraint_use_statements' => $constraintClasses,
            //                'shortClassName' => $formClassDetails->getShortName(),
            ]
        );


//        unlink($generatedFilename); // we need a --force flag
        $generator->writeChanges();
        print file_get_contents($generatedFilename);

        $this->writeSuccessMessage($io);
        dd($this->templatePath, $generatedFilename);

        // TODO: Implement generate() method.
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement @method string getCommandDescription()
    }
}
