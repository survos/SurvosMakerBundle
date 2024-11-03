<?php

namespace Survos\Bundle\MakerBundle\Command;

use Nette\PhpGenerator\PhpNamespace;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Survos\Bundle\MakerBundle\Service\GeneratorService;
use Survos\Bundle\MakerBundle\Service\MakerService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;
use function Symfony\Component\String\u;

#[AsCommand('survos:make:controller-class', 'Generate a controller class OR a method')]
// class must exist before the method.
//
final class GenerateControllerCommand extends InvokableServiceCommand
{
    public function __construct(
        private GeneratorService $generatorService,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    )
    {
        parent::__construct();

    }
    use RunsCommands;
    use RunsProcesses;

    public function __invoke(
        IO     $io,
        #[Argument(description: 'Controller class name, e.g. App')]
        string $name,
        #[Option('routeName', description: 'controller route name (e.g. admin_do_something')]
        ?string $routeName = null,
        #[Option('method', 'm', null, 'method name, default to routeName')]
        string $method = null,

        #[Option('invokable', 'i', null, 'make an invokable controller')]
        bool   $invokable = true,
        #[Option('template', 't', null, 'template name with path')]
        string $templateName = '',
        #[Option(null, 's', null, 'secure route with a role')]
        string $security = '',
        #[Option(null, 'c', null, 'cache')]
        string $cache = '',
        #[Option('route', 'r', null, 'route, defaults to /[route]')]
        string $route = '',
        #[Option('class-route', 'cr', null, 'class route')]
        string $classRoute = '',

        #[Option(description: 'namespace to use (will determine file location)', shortcut: 'ns')]
        string $namespace = '',

        #[Option('overwrite', 'force', null, "Overwrite the file if it exists")]
        bool   $force = false
    ): void
    {
        if (empty($namespace)) {
            $namespace = 'App\\Controller';
        }
        if (!u($name)->endsWith('Controller')) {
            $name .= 'Controller';
        }

        // for twig stuff, see https://github.com/zenstruck/twig-service-bundle
        // @todo: instead of generating the controller, read it and append/replace the new method
        $ns = $this->generatorService->generateController($name, $namespace, $routeName, $route, $security, $cache, $templateName, $classRoute);

        $class = $ns->getClasses()[array_key_first($ns->getClasses())];
        if ($routeName) {
            $this->generatorService->addMethod($class, $routeName);
            $this->createTemplate($name, $routeName, $templateName, $force);
        }

        $path = $this->generatorService->namespaceToPath($namespace, $this->projectDir);
        $filename = $path . '/';
        foreach (explode(':', $name) as $part) {
            $filename .= u($part)->title();
        }
        $filename .= '.php';

        if (!file_exists($filename) || $force) {
            file_put_contents($filename, '<?php ' . "\n\n" . $ns);
        } else {
            throw new \Exception("$filename already exists");
        }

        $io->success(sprintf('controller %s generated.', $filename));
    }

    private function createTemplate(string $controllerName,
                                    string $routeName,
                                    string $templateName=null,
                                    bool $force = false)
    {
        if (empty($templateName)) {
            $templatePrefix = u($controllerName)->replace('Controller', '')->lower();
            $templateName = "$templatePrefix/$routeName";
        }
        if (!u($templateName)->endsWith('.html.twig')) {
            $templateName .= ".html.twig";
        }

        $templatePath = 'templates/' . $templateName;
        if (!file_exists($templatePath) || $force) {
            $dir = pathinfo($templatePath, PATHINFO_DIRNAME);
            if (!file_exists($dir)) {
                mkdir($dir, recursive: true);
            }
            // @todo: get template paths
            $fn = __DIR__ . '/../../twig/symfony.html.twig';
            assert(file_exists($fn), $fn);
            $templateCode = file_get_contents($fn);

            file_put_contents(getcwd() . '/' . $templatePath, $templateCode);
        }

    }

}
