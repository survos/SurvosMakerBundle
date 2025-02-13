<?php

/*
 * @deprecated()
 */
namespace Survos\Bundle\MakerBundle\Maker;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Survos\Bundle\MakerBundle\Service\MakerService;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\CoreBundle\Entity\RouteParametersTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

final class MakeMethod extends AbstractMaker
{
    public function __construct(
        private Generator $generator,
        private string $templatePath
    ) {
    }

    public static function getCommandName(): string
    {
        return 'survos:make:method';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new class method ';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addOption('className', 'c', InputOption::VALUE_OPTIONAL, 'The name of the existing class (e.g. <fg=yellow>App/Service/MathService</> or FQCN)')
            ->addOption('methodName', 'm', InputOption::VALUE_OPTIONAL, 'The name of the method (e.g. <fg=yellow>calculateSum</>)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'overwrite this method if it already exists')
            ->addOption('body', 'b', InputOption::VALUE_REQUIRED, 'filename for the PHP body, requires --force')
            ->setHelp("Create or update a method in a class")
        ;
    }


    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $path = MakerService::namespaceToPath('App\\Controller\\XYZ');
        dd($path);

        $composerJson = json_decode(
            file_get_contents($monorepoComposerJsonFilepath = getcwd() . '/composer.json'),
            true
        );
        dd($composerJson);
        $autoLoad = $composerJson['autoload'];
        $bundleNamespace = "$vendor\\$name\\";



// or use the PsrPrinter for output in accordance with PSR-2 / PSR-12 / PER
// echo (new Nette\PhpGenerator\PsrPrinter)->printNamespace($namespace);
        $class = new ClassType($className = 'DemoController', new PhpNamespace($ns = 'App\\Controller'));
//        assert($class->getNamespace()->getName() == $ns);
        $class
            ->setFinal()
            ->setExtends(AbstractController::class)
//            ->addImplement(Countable::class)
            ->addComment("Class description.\nSecond line\n")
            ->addComment('@property-read Nette\Forms\Form $form');

        $printer = new \Nette\PhpGenerator\Printer;
        $content =   $printer->printClass($class);
        dd($content);

        file_put_contents($fn = "src/Controller/$className.php", '<?php' . "\n\n" . $class);
        dd((string)$class, $fn);

// generate code simply by typecasting to string or using echo:
        echo $class;


        // parse out the input if it has a namespace or filename in the piped-in input, e.g.
        $inputSteam = ($input instanceof StreamableInputInterface) ? $input->getStream() : null;
        $content = $inputSteam ? stream_get_contents($inputSteam) : null;
        if ($content) {
            dd($content, file_get_contents($content));
        }

        // if the first line of the body is a filename or a namespace, use it instead of the CLI options

        //
        //            dd($contentFile = $input->getOption('body'), file_get_contents($contentFile));
        //        if ($input->getOption('no-interaction')) {
        //        }

        $reflectionClass = new \ReflectionClass($className = $input->getArgument('className'));

        // first, see if the method already exists.
        if ($reflectionClass->hasMethod($methodName = $input->getArgument('name'))) {
            if (!$input->getOption('force')) {
                $io->error("Method $methodName already exists in $className, use --force to overwrite it");
            } else {
                $reflectionMethod = $reflectionClass->getMethod($methodName);
                $source = file_get_contents($reflectionMethod->getDeclaringClass()->getFileName());
                $sourceLines = explode("\n", $source);
                $source = join("\n", array_splice($sourceLines, $reflectionMethod->getStartLine() - 1, $reflectionMethod->getEndLine() - $reflectionMethod->getStartLine()));
                dd($source, $content);
            }
        }

        dd($reflectionClass->getFileName(), $content);

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
            [
                'content' => $content,
                'use_statements' => $useStatements,
            ]
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
