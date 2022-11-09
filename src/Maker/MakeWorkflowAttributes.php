<?php

namespace Survos\Bundle\MakerBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Survos\Bundle\MakerBundle\Renderer\ParamConverterRenderer;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Workflow\Registry;
use Symfony\Config\FrameworkConfig;

use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeWorkflowAttributes extends AbstractMaker implements MakerInterface
{
    public function __construct(
        private DoctrineHelper $entityHelper,
        private string $templatePath
    ) {
    }

    public static function getCommandName(): string
    {
        return 'survos:make:workflow-attributes';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a workflow configuration existing workflow')
            ->addArgument('bound-class', InputArgument::REQUIRED, 'The name of Entity or fully qualified model class name')
            ->addArgument('flowCode', InputArgument::OPTIONAL, 'the workflow name, if more than one')
            ->setHelp(file_get_contents(__DIR__ . '/../../help/MakeParamConverter.txt'))
        ;

        $inputConf->setArgumentAsNonInteractive('bound-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('bound-class')) {
            $argument = $command->getDefinition()->getArgument('bound-class');

            $entities = $this->entityHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setValidator(fn ($answer) => Validator::existsOrNull($answer, $entities));
            $question->setAutocompleterValues($entities);
            $question->setMaxAttempts(3);

            $input->setArgument('bound-class', $io->askQuestion($question));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $boundClass = $input->getArgument('bound-class');
        $boundClassDetails = null;

        if (null !== $boundClass) {
            $boundClassDetails = $generator->createClassNameDetails(
                $boundClass,
                'Entity\\'
            );

            $classDetails = new ClassDetails($boundClassDetails->getFullName());
        }

        $useStatements = new UseStatementGenerator([
            $boundClassDetails->getFullName(),
            FrameworkConfig::class,
        ]);

        $reflection = new \ReflectionClass($boundClassDetails->getFullName());
        $constants = array_keys($reflection->getConstants());
        $workflowConfigFilename = $generator->getRootDirectory() . sprintf('/config/packages/%s_workflow.php', strtolower($boundClassDetails->getShortName()));
        $generator->generateFile(
            $workflowConfigFilename,
            $this->templatePath . 'Workflow/config/_workflow.tpl.php',
            $v = [
                'places' => array_filter($constants, fn ($c) => str_starts_with($c, 'PLACE_')),
                'transitions' => array_filter($constants, fn ($c) => str_starts_with($c, 'TRANSITION_')),
                'entity_full_class_name' => $boundClassDetails->getFullName(),
                'entityName' => $boundClassDetails->getShortName(),
                'use_statements' => $useStatements,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new workflow class and start customizing it.',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    public static function getCommandDescription(): string
    {
        return "Make workflow from constants";
    }
}
