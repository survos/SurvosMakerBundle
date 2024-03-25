<?php

namespace Survos\Bundle\MakerBundle\Command;

use Nette\PhpGenerator\PhpNamespace;
use Survos\Bundle\MakerBundle\Service\MakerService;
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\CoreBundle\Entity\RouteParametersTrait;
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
use Zenstruck\Console\ConfigureWithAttributes;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;
use function Symfony\Component\String\u;

#[AsCommand('survos:make:controller-class', 'Generate a controller class')]
final class GenerateControllerCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __invoke(
        IO     $io,
        #[Argument(description: 'Controller class name, e.g. App')]
        string $name,
        #[Argument(description: 'controller method name')]
        string $routeName,
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
        #[Option('route', 'r', null, 'route, defaults to /[route-name]')]
        string $route = '',
        #[Option('class-route', 'cr', null, 'class route')]
        string $classRoute = '',

        #[Option(description: 'namespace to use (will determine file location)', shortcut: 'ns')]
        string $namespace = '',

        #[Option('overwrite', 'force', null, "Overwrite the file if it exists")]
        bool   $force = false
    ): void
    {
        if (!u($name)->endsWith('Controller')) {
            $name .= 'Controller';
        }
        if (empty($route)) {
            $route = "/$routeName";
        }
        if (empty($method)) {
            $method = u($route)->snake()->toString();
        }
        if (empty($namespace)) {
            $namespace = 'App\\Controller';
        }
        if (empty($templateName)) {
            $templatePrefix = u($name)->replace('Controller', '')->lower();
            $templateName = "$templatePrefix/$routeName";
        }
        if (!u($templateName)->endsWith('.html.twig')) {
            $templateName .= ".html.twig";
        }

        $templatePath = 'templates/' . $templateName;
        if (!file_exists($templatePath)) {
            $dir = pathinfo($templatePath, PATHINFO_DIRNAME);
            if (!file_exists($dir)) {
                mkdir($dir, recursive: true);
            }

            file_put_contents(getcwd() . '/' . $templatePath, 'template content');
        }

        $path = MakerService::namespaceToPath($namespace);
        $filename = $path . '/';
        foreach (explode(':', $name) as $part) {
            $filename .= u($part)->title();
        }
        $filename .= '.php';

        // first, generate the controller class if it doesn't exist

        $phpCode = $this->generateController($namespace, $name, $routeName, $route, $security, $cache, $templateName, $classRoute);
        if (!file_exists($filename) || $force) {
            file_put_contents($filename, $phpCode);
        } else {
            throw new \Exception("$filename already exists");
        }

        $io->success(sprintf('controller %s generated.', $filename));
    }

    private function generateController(string $namespaceName,
                                        string $controllerName,
                                        string $routeName,
                                        string $route,
                                        string $security,
                                        string $cache,
                                        string $templateName,
                                        string $classRoute

    ): string
    {
        $namespace = new PhpNamespace($namespaceName);
        $useClasses = [
            AbstractController::class,
            Response::class,
            Route::class,
            Request::class,
            Autowire::class,
            Template::class
        ];
        if ($security) {
            $useClasses[] = IsGranted::class;
        }
        array_map(fn($useClass) => $namespace->addUse($useClass), $useClasses);

        $class = $namespace->addClass($controllerName);
        $class
            ->setExtends(AbstractController::class);
        if ($classRoute) {
            $class->addAttribute(Route::class, [$classRoute]);
        }
//            ->addImplement(RouteParametersInterface::class) // will be simplified to A
//            ->addTrait(RouteParametersTrait::class); // will be simplified to AliasedClass


        return sprintf("<?php \n\n%s", $namespace);

    }

    public function addMethod()
    {
        $method = $class->addMethod('__construct');
//        $method->addPromotedParameter('name', null)
//            ->addAttribute(Autowire::class)
//            ->setType('string')
//            ->setNullable(true);
//        $method->addPromotedParameter('args', [])
//            ->setPrivate();

        $method = $class->addMethod($routeName)
            ->addAttribute(Template::class, [$templateName])
            ->addAttribute(Route::class, ['path' => $route, 'name' => $routeName])
            ->setReturnType('array');
        if ($security)
            $method->addAttribute(IsGranted::class, [$security]);
//        $method->addComment('@return ' . $namespace->simplifyType('Foo\D')); // we manually simplify in comments
        $method->addParameter('request')
            ->setType(Request::class);
        $body = <<< 'END'
        return [];
END;
        $method->setBody($body);

    }
}
