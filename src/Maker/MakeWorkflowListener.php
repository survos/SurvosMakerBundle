<?php

namespace Survos\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Workflow\Registry;

use function Symfony\Component\String\u;

final class MakeWorkflowListener extends AbstractMaker implements MakerInterface
{
    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private Generator $generator,
        private ?Registry $registry = null
    ) {
    }

    public static function getCommandName(): string
    {
        return 'survos:make:workflow-listener';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity'))
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite if it already exists.')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $io->success('Workflow is now listening for events, open .. to react.');

        return Command::SUCCESS;
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $entityClassDetails = $generator->createClassNameDetails(
            Validator::entityExists($input->getArgument('entity-class'), $this->doctrineHelper->getEntitiesForAutocomplete()),
            'Entity\\'
        );

        $listererClassDetails = $generator->createClassNameDetails(
            $entityClassDetails->getShortName() . 'TransitionListener', // or WorkflowListener? StateMachineListener?
            'Workflow\\Listener\\'
        );

        $fullClassName = $listererClassDetails->getFullName();
        // to get the workflow name from the workflows
        $workflow = $this->registry->get(new ($entityClassDetails->getFullName())());
        //        dd($workflow->getName());
        $workflowName = constant($entityClassDetails->getFullName() . '::WORKFLOW');

        // https://symfony.com/doc/current/workflow.html#using-events

        $skeletonPath = __DIR__ . '/../Resources/skeleton/';
        $templatesPath = 'Workflow/Listener/';
        $template = 'WorkflowListener.php.tpl';
        $generatedFilename = $this->generator->generateClass(
            $fullClassName,
            $skeletonPath . $templatesPath . $template,
            $v = [
                'entity_full_class_name' => $entityClassDetails->getFullName(),
                'full_class_name' => $fullClassName,
                'shortClassName' => $listererClassDetails->getShortName(),
                'entityName' => $entityClassDetails->getShortName(),
                //                'transitions' => $workflow->getDefinition()->getTransitions(),
                'workflowName' => $workflowName,
                'constantsMap' => array_flip($entityClassDetails->getFullName()::getConstants('TRANSITION_')),

                //                'entity_class_name' => $boundClassDetails ? $boundClassDetails->getShortName() : null,
                //                'form_fields' => $fields,
                //                'entity_var_name' => $entityVarSingular,
                //                'entity_unique_name' => $entityVarSingular . 'Id',
                //                'field_type_use_statements' => $mergedTypeUseStatements,
                //                'constraint_use_statements' => $constraintClasses,
                //                'shortClassName' => $formClassDetails->getShortName(),
            ]
        );

        //        $templatesPath = Str::asFilePath($entityClassDetails->getRelativeNameWithoutSuffix());
        //        dd($templatesPath);

        //        $x = $generator->getFileContentsForPendingOperation($generatedFilename);
        //        dd($x);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement @method string getCommandDescription()
    }
}
