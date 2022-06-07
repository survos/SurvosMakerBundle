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

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeWorkflow extends AbstractMaker implements MakerInterface
{
    public function __construct(private DoctrineHelper $entityHelper, private ParamConverterRenderer $paramConverterRenderer, private string $templatePath, private ParameterBagInterface $bag,)
    {
        dd($this->bag->all());
    }

    public static function getCommandName(): string
    {
        return 'survos:make:workflow';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a workflow configuration from PLACE_ and _TRANSITION constants')
            ->addArgument('bound-class', InputArgument::REQUIRED, 'The name of Entity or fully qualified model class name')
       //     ->addArgument('name', InputArgument::OPTIONAL, sprintf('The name of the ParamConverter class (e.g. <fg=yellow>%sType</>)', Str::asClassName(Str::getRandomTerm())))
            ->setHelp(file_get_contents(__DIR__.'/../../help/MakeParamConverter.txt'))
        ;

        $inputConf->setArgumentAsNonInteractive('bound-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('bound-class')) {
            $argument = $command->getDefinition()->getArgument('bound-class');

            $entities = $this->entityHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setValidator(function ($answer) use ($entities) {return Validator::existsOrNull($answer, $entities); });
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

            $doctrineEntityDetails = $this->entityHelper->createDoctrineDetails($boundClassDetails->getFullName());

            if (null !== $doctrineEntityDetails) {
                $formFields = $doctrineEntityDetails->getFormFields();
            } else {
                $classDetails = new ClassDetails($boundClassDetails->getFullName());
                $formFields = $classDetails->getFormFields();
            }
        }

        $useStatements = new UseStatementGenerator([
        ]);

        dd($generator->getRootDirectory());
        $generator->generateFile(
            $extensionClassNameDetails->getFullName(),
            $this->templatePath . 'config/Workflow.tpl.php',
            ['use_statements' => $useStatements]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new extension class and start customizing it.',
            'Find the documentation at <fg=yellow>http://symfony.com/doc/current/templating/twig_extension.html</>',
        ]);


        $paramConverterClassNameDetails = $generator->createClassNameDetails(
            $boundClassDetails->getShortName(),
            'Request\\ParamConverter\\',
            'ParamConverter'
        );

//        $templatesPath = Str::asFilePath($paramConverterClassNameDetails->getRelativeNameWithoutSuffix());

        $formFields = ['field_name' => null];


        $this->paramConverterRenderer->render(
            $paramConverterClassNameDetails,
            $formFields,
            $boundClassDetails
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Add fields to your form and start using it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/forms.html</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            AbstractType::class,
            // technically only form is needed, but the user will *probably* also want validation
            'form'
        );

        $dependencies->addClassDependency(
            Validation::class,
            'validator',
            // add as an optional dependency: the user *probably* wants validation
            false
        );

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm',
            false
        );
    }

    static function getCommandDescription(): string
    {
        return "Check request for object";
    }
}
