<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Survos\Bundle\MakerBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\String\Inflector\EnglishInflector;
//use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function Symfony\Component\String\u;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 * @author Tac Tacelosky <tacman@gmail.com>
 */
class MakeBundle extends AbstractMaker implements MakerInterface
{

    public function __construct(private Generator $generator, private string $templatePath,
                                private string $vendor,
                                private string $bundleName,
    )
    {
    }

    public static function getCommandName(): string
    {
        return 'survos:make:bundle';
    }
    static function getCommandDescription(): string
    {
        return "Makes a bundle class";
    }


    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates a Symfony 6.1 bundle in a new directory')
            // echo "maker: { root_namespace: Survos }" > config/packages/maker.yaml
            ->addArgument('name', InputArgument::OPTIONAL, 'The bundle name part of the namespace', 'SurvosFoo')
            ->addArgument('vendor', InputArgument::OPTIONAL, 'The vendor part of the namespace', 'Survos')
//            ->addArgument('directory', InputArgument::OPTIONAL, 'The directory (relative to the project root) where the bundle will be created', '..')
//            ->addArgument('bundle-class', InputArgument::OPTIONAL, sprintf('The class name of the bundle to create (e.g. <fg=yellow>%sBundle</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption('twig', null, InputOption::VALUE_OPTIONAL, "Create and register a Twig Extension", 'TwigExtension')
            ->setHelp(file_get_contents(__DIR__.'/../../help/MakeCrud.txt'))
        ;

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('name')) {
            $argument = $command->getDefinition()->getArgument('name');
            $question = new Question($argument->getDescription());
            $value = $io->askQuestion($question);

            $input->setArgument('name', $value);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $vendor = $input->getArgument('vendor');
        $name = $input->getArgument('name');

        // if the namespace doesn't exist, die and prompt user to reload the map
        $json = json_decode(file_get_contents("composer.json"));  // object, not array (no second arg)
        $bundleNamespace = "$vendor\\$name\\";
        $psr = $json->autoload->{'psr-4'};
        if (!property_exists($psr, $bundleNamespace)) {

            $json->{"autoload"}->{"psr-4"}->{$bundleNamespace} = "lib/temp";  // object properties, not array indexes
            file_put_contents("composer.json", $newjson = json_encode($json, JSON_PRETTY_PRINT && JSON_UNESCAPED_SLASHES && JSON_UNESCAPED_UNICODE));
            $io->write("Please run composer dump-autoload to create a bundle structure for $bundleNamespace\n");
            return;
        }


        // after generation, remove this line and tell user to load bundle from new directory

        $extensionClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('name'),
            '\\',
            'Bundle'
        );


        $useStatements = new UseStatementGenerator([
            DefinitionConfigurator::class,
            AbstractBundle::class,
            ContainerBuilder::class,
            ContainerConfigurator::class,
            Bundle::class,
        ]);

        $classPath = $generator->generateClass(
            $extensionClassNameDetails->getFullName(),
            $this->templatePath .  'bundle/src/Bundle.tpl.php',
            ['use_statements' => $useStatements]
        );
        $classDir = pathinfo($classPath, PATHINFO_DIRNAME);
        // composer belongs above src
        $snake = u($this->bundleName)->snake()->replace('_', '-');


        $generator->generateFile(
            $classDir . '/../composer.json',
            $this->templatePath .  'bundle/composer.tpl.json',
            $x = [
                'vendor' => $vendor,
                'bundleName' => $this->bundleName,
                'name' => sprintf("%s/%s", u($vendor)->lower(), $snake)
            ]
        );

//        dd($x, $classDir, $generator->getRootDirectory(), $generator->getRootNamespace(), __FILE__, __LINE__);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Remove psr-4 autoload and add to bundle path to composer',
            'Find the documentation at <fg=yellow>https://github.com/survos/maker-bundle/doc/maker-bundle.md</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            AbstractExtension::class,
            'twig'
        );
    }


}
