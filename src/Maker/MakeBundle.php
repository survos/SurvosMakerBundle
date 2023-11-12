<?php

/*
 * This file follows the model of the Symfony MakerBundle package.
 *
 */

namespace Survos\Bundle\MakerBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Inflector\InflectorFactory;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symplify\ComposerJsonManipulator\ComposerJsonFactory;
use Symplify\ComposerJsonManipulator\FileSystem\JsonFileManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;


use function Symfony\Component\String\u;

/**
 * @author Tac Tacelosky <tacman@gmail.com>
 */
class MakeBundle extends AbstractMaker implements MakerInterface
{
    private string $bundleName;

    public function __construct(
        private string              $templatePath,
        private string              $bundlePath,
        private JsonFileManager     $jsonFileManager,
        private ComposerJsonFactory $composerJsonFactory
    )
    {
    }

    public static function getCommandName(): string
    {
        return 'survos:make:bundle';
    }

    public static function getCommandDescription(): string
    {
        return "Create a simple Symfony bundle in the survos monorepo.  Must be run from the main repository (survos/survos) ";
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates a Symfony 6.1+} bundle in a new directory')
            // echo "maker: { root_namespace: Survos }" > config/packages/maker.yaml
//            ->addArgument('action', InputArgument::REQUIRED, 'init or library or local-bundle or remote-bundle', null)
            ->addArgument('name', InputArgument::OPTIONAL, 'The bundle name part of the namespace')
            ->addArgument('vendor', InputArgument::OPTIONAL, 'The vendor part of the namespace')
//            ->addArgument('directory', InputArgument::OPTIONAL, 'The directory (relative to the project root) where the bundle will be created', '..')
//            ->addArgument('bundle-class', InputArgument::OPTIONAL, sprintf('The class name of the bundle to create (e.g. <fg=yellow>%sBundle</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption('twig', null, InputOption::VALUE_OPTIONAL, "Create and register a Twig Extension", 'TwigExtension')
            ->setHelp(file_get_contents(__DIR__ . '/../../help/MakeBundle.txt'));

//        $inputConfig->setArgumentAsNonInteractive('entity-class'); //??
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('name')) {
            $argument = $command->getDefinition()->getArgument('name');
            $question = new Question($argument->getDescription());
            $value = $io->askQuestion($question);

            $input->setArgument('name', $value);
        }
        $this->bundleName = $input->getArgument('name');
    }

    public function example()
    {
        $composerJson = $this->composerJsonFactory->createFromFilePath(getcwd() . '/composer.json');
        $autoLoad = $composerJson->getAutoload();
        $autoLoad['psr-4']['Cool\\Stuff\\'] = './lib/';
        $composerJson->setAutoload($autoLoad);
        $this->jsonFileManager->printComposerJsonToFilePath($composerJson, $composerJsonFilepath = $composerJson->getFileInfo()->getRealPath());
        echo $composerJsonFilepath . ' has been updated';
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $vendor = $input->getArgument('vendor');
        $name = $input->getArgument('name');

        // if the namespace doesn't exist, die and prompt user to reload the map
        //        $json = json_decode(file_get_contents($composerJsonFilepath = "composer.json"));  // object, not array (no second arg)
        $bundleNamespace = "$vendor\\$name\\";
        // ↓ instance of \Symplify\ComposerJsonManipulator\ValueObject\ComposerJson
        $composerJson = $this->composerJsonFactory->createFromFilePath(getcwd() . '/composer.json');
        $autoLoad = $composerJson->getAutoload();
        //        dump($composerJson->getPsr4AndClassmapDirectories(), $composerJson->getAutoload()['psr-4']);
        //        dd($composerJson->getFileInfo()->getRealPath());
        // ...
        $snake = u($name)->snake()->replace('_', '-');
        //            $snakeName = sprintf("%s/%s", u($vendor)->lower(), $snake);
        $bundlePath = $this->bundlePath . '/' . $snake . '/src/';
        $snakeName = strtolower($vendor) . '/' . $snake;

        /*
        *      // Full class names can also be passed. Imagine the user has an autoload
        *      // rule where Cool\Stuff lives in a "lib/" directory
        *      // Cool\Stuff\BalloonController
        *      $gen->createClassNameDetails('Cool\\Stuff\\Balloon', 'Controller', 'Controller');
        */
        //        $details = $generator->createClassNameDetails('Cool\\Stuff\\Balloon', 'Controller', 'Controller');
        //        dd($details->getFullName());

        if (!array_key_exists($bundleNamespace, $autoLoad['psr-4'])) {
            //            "Survos\\ApiGrid\\": "packages/api-grid-bundle/src/",
            //            $json->{"autoload"}->{"psr-4"}->{$bundleNamespace} = $bundlePath;
            //            $json["autoload"]["psr-4"][$bundleNamespace] = $bundlePath;

            $autoLoad['psr-4'][$bundleNamespace] = $bundlePath;
            $composerJson->setAutoload($autoLoad);

            // @todo: use jq from cli instead.  https://github.com/symplify/composer-json-manipulator
            //            dd($autoLoad);

            //            $io->write("Add the following to composer.json, then run composer dump-autoload to continue");
            //            $io->write(<<< EOL
            //        "psr-4": {
            //            "$bundleNamespace\\": "$this->bundlePath",
            //            "Survos\\ApiGrid\\": "packages/api-grid-bundle/src/",
            //
            //EOL
            //);

            //            $json = json_decode(json_encode($json), true);
            //            $composerJson = $this->composerJsonFactory->createFromArray((array)$json);

            $composerJsonFilepath = $composerJson->getFileInfo()->getRealPath();
            //            dd($bundleNamespace, $this->bundlePath, $bundlePath, $composerJson, $composerJsonFilepath);
            $this->jsonFileManager->printComposerJsonToFilePath($composerJson, $composerJsonFilepath);

            $message = sprintf(
                '"%s" was updated to use %s, run composer dump to reload the class map ',
                $composerJsonFilepath,
                $this->bundleName
            );
            $io->note($message);

            //
            //            dd($composerJson->getAbsoluteAutoloadDirectories(), $composerJson->getPsr4AndClassmapDirectories());
            //
            //            dd($composerJson->getPsr4AndClassmapDirectories(), $composerJson->getAllClassmaps());
            //            file_put_contents("composer.json", $newjson = json_encode($json, JSON_PRETTY_PRINT && JSON_UNESCAPED_SLASHES && JSON_UNESCAPED_UNICODE));
            $io->write("Please run composer dump-autoload to create a bundle structure for $bundleNamespace\nTHEN add services, then run ");

            return;
        }

        // after generation, remove this line and tell user to load bundle from new directory
        $nameWithVendor = $vendor . '\\' . $name;

        $useStatements = new UseStatementGenerator([
            AbstractExtension::class,
            TwigFilter::class,
            TwigFunction::class,
        ]);

//        dd($nameWithVendor, $this->bundleName, $templateName);
        $classPath = $generator->generateClass(
            $nameWithVendor . '\\Twig\\TwigExtension',
            realpath($this->templatePath . 'twig/Extension.tpl.php'),
            variables: [
                //                'class_name' => $className,
//                'actualClassName' => $className,
                'use_statements' => $useStatements,
            ]
        );
        $generator->writeChanges();

        $extensionClassNameDetails = $generator->createClassNameDetails(
            $nameWithVendor,
            //            $name,
            //            '\\' . $vendor,
            //            '',
            'Extension'
        );
        //        assert($extensionClassNameDetails->getRelativeName())
        //        dump($extensionClassNameDetails->getFullName(), $vendor, $name, $nameWithVendor, __LINE__, __FILE__);
        //        assert(false);

        $useStatements = new UseStatementGenerator([
            DefinitionConfigurator::class,
            AbstractBundle::class,
            ContainerBuilder::class,
            ContainerConfigurator::class,
        ]);
        $className = str_replace('\\', '', $nameWithVendor);
        //        dd($useStatements, $className, $nameWithVendor, Str::getShortClassName($className));

        $templateName = realpath($this->templatePath . 'bundle/src/Bundle.tpl.php');
//        dd($nameWithVendor, $this->bundleName, $templateName);
        $classPath = $generator->generateClass(
            $nameWithVendor . '\\' . $nameWithVendor,
            $templateName,
            variables: $vars=[
                'templateName' => $templateName,
                //                'class_name' => $className,
                'actualClassName' => $className,
                'use_statements' => $useStatements,
            ]
        );
        $generator->writeChanges();

        // hack, because something is wrong with the classmap lookup
        $classDir = str_replace('/.php', '', pathinfo($classPath, PATHINFO_DIRNAME));

        // composer belongs above src
        //        dd($classDir, $extensionClassNameDetails->getFullName(), $vendor, $name, $nameWithVendor, __LINE__);
        //        dd($snakeName, $snake, __LINE__, $classDir, $extensionClassNameDetails);
        $generator->generateFile(
            $classDir . '/../composer.json',
            $this->templatePath . 'bundle/composer.tpl.json',
            $x = [
                'vendor' => $vendor,
                'bundleName' => $this->bundleName,
                'name' => $snakeName,
            ]
        );

        //                dd($x, $classDir, $generator->getRootDirectory(), $generator->getRootNamespace(), __FILE__, __LINE__);

        $generator->writeChanges();

        // hack from the pit of hell
//        rename($classPath, $actualFilename);

        $this->writeSuccessMessage($io);

        $io->text([
            sprintf('vendor/bin/phpstan analyze packages/%s/', $this->bundleName),
            "modify $classPath to inject dependencies",
            'Develop the bundle here, but to use in another application use monorepo-split',
            'OR Remove psr-4 autoload and add to bundle path to composer, or composer req to get the recipes',
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
