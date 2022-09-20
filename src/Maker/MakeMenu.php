<?php

namespace Survos\Bundle\MakerBundle\Maker;

use Knp\Menu\ItemInterface;
use Survos\BootstrapBundle\Event\KnpMenuEvent;
use Survos\BootstrapBundle\Menu\MenuBuilder;
use Survos\BootstrapBundle\Traits\KnpMenuHelperTrait;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
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

final class MakeMenu extends AbstractMaker implements MakerInterface
{
    public function __construct(
        private Generator $generator,
        private string $templatePath
    ) {
    }

    public static function getCommandName(): string
    {
        return 'survos:make:menu';
    }


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

        
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $shortName = $input->getArgument('menuClassName');
        $classNameDetails = $generator->createClassNameDetails(
            $shortName,
            'EventSubscriber\\',
            'MenuEventSubscriber'
        );

        $useStatements = new UseStatementGenerator([
            EventSubscriberInterface::class,
            KnpMenuHelperTrait::class,
            ItemInterface::class,
            MenuBuilder::class,
            KnpMenuEvent::class,
            AsEventListener::class,
            OptionsResolver::class,
            AuthorizationCheckerInterface::class,
        ]);

        $generatedFilename = $this->generator->generateClass(
            $classNameDetails->getFullName(),
            __DIR__ . '/../../templates/skeleton/Menu/MenuEventListener.tpl.twig',
            $v = [
                'entity_full_class_name' => $classNameDetails->getFullName(),
                'use_statements' => $useStatements,
            ]
        );

        //        unlink($generatedFilename); // we need a --force flag
        $generator->writeChanges();
        print file_get_contents($generatedFilename);

        $this->writeSuccessMessage($io);

        
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement @method string getCommandDescription()
    }
}
