<?php

namespace Survos\Bundle\MakerBundle\Maker;

use Knp\Menu\ItemInterface;
use Survos\BootstrapBundle\Event\KnpMenuEvent;
use Survos\BootstrapBundle\Menu\MenuBuilder;
use Survos\BootstrapBundle\Service\MenuService;
use Survos\BootstrapBundle\Traits\KnpMenuHelperInterface;
use Survos\BootstrapBundle\Traits\KnpMenuHelperTrait;
use Survos\WorkflowBundle\Attribute\Place;
use Survos\WorkflowBundle\Attribute\Transition;
use Survos\WorkflowBundle\Attribute\Workflow;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\RouterInterface;
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
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Twig\Extension\AbstractExtension;

final class MakeWorkflow extends AbstractMaker implements MakerInterface
{
    public function __construct(
        private Generator $generator,
        private RouterInterface $router,
        private string $templatePath
    ) {
    }

    public static function getCommandDescription(): string
    {
        return 'Generate a Workflow class';
    }

    public static function getCommandName(): string
    {
        return 'survos:make:workflow';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('className', InputArgument::REQUIRED, 'Workflow Class Name')
            ->addArgument('places', InputArgument::REQUIRED, 'comma-separated list of places (e.g. new,approved,rejected)')
            ->addArgument('transitions', InputArgument::REQUIRED, 'comma-separated list of transitions, (approve,reject)')
            ->addOption('entityClassName', 'c', InputOption::VALUE_OPTIONAL, 'entity class supported by this workflow')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $io->success('create interface and listeners for workflow events.');
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
//        $dependencies->addClassDependency(
//            AbstractExtension::class,
//            'twig'
//        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        // generate the interface with the WORKFLOW_NAME and places
        $shortName = $input->getArgument('className');
        $classNameDetails = $generator->createClassNameDetails(
            $shortName,
            'Workflow\\',
            suffix: 'WorkflowInterface'
        );
        $useStatements = new UseStatementGenerator([
            Place::class,
            Transition::class,
        ]);

        $generatedFilename = $this->generator->generateClass(
            $classNameDetails->getFullName(),
            __DIR__ . '/../../templates/skeleton/Workflow/WorkflowInterface.tpl.twig',
            $v = [
                'entity_full_class_name' => $classNameDetails->getFullName(),
                'places' => explode(",", $input->getArgument('places')),
                'transitions' => explode(",", $input->getArgument('transitions')),
                'use_statements' => $useStatements,
            ]
        );

        $shortName = $input->getArgument('className');
        $entityClass = $input->getOption('entityClassName');

        $classNameDetails = $generator->createClassNameDetails(
            $shortName,
            'Workflow\\',
            suffix: 'Workflow'
        );

        $useStatements = new UseStatementGenerator([
            AsTransitionListener::class,
            AsGuardListener::class,
            GuardEvent::class,
            TransitionEvent::class,
            Workflow::class,
            $entityClass,
        ]);

        foreach ($this->router->getRouteCollection() as $routeName => $route) {
            //
//            dd($routeName, $route);
        }

        $generatedFilename = $this->generator->generateClass(
            $classNameDetails->getFullName(),
            __DIR__ . '/../../templates/skeleton/Workflow/Workflow.tpl.twig',
            $v = [
                'entity_full_class_name' => $entityClass,
                'workflow_class_name' => $classNameDetails->getFullName(),
                'transitions' => explode(",", $input->getArgument('transitions')),
                'use_statements' => $useStatements,
            ]
        );

        //        unlink($generatedFilename); // we need a --force flag
        $generator->writeChanges();
        //        print file_get_contents($generatedFilename);

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new menu class and start customizing it.',
        ]);
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement @method string getCommandDescription()
    }
}
