<?php

declare(strict_types=1);

namespace Survos\Bundle\MakerBundle\Service;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Type;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function Symfony\Component\String\u;

class GeneratorService
{
    //    private PropertyAccessor $propertyAccessor;
    public function __construct(
        private MakerService $makerService,
    )
    {
    }

    public static function namespaceToPath(string $namespace, string $projectDir): ?string
    {
        $x = include $projectDir . "/vendor/composer/autoload_psr4.php";
        $result = null;
        $parts = explode('\\', $namespace);
        $paths = [];
        do {
            $namespace = join('\\', $parts);
            $query = $namespace . '\\';
            if (array_key_exists($query, $x)) {
                array_unshift($paths, $x[$query][0]);
                return join('/', $paths);
            } else {
                array_unshift($paths, array_pop($parts));
            }
        } while (!$result && count($parts));
        return null;
    }


    public function getProjectDir(): string
    {
        return $this->makerService->getProjectDir();

    }

    public function generateController(
        string $controllerName,
        string $namespaceName = 'App\\Controller',
        ?string $routeName = null,
        ?string $route = null,
        ?string $security = null,
        ?string $cache = null,
        ?string $templateName = null,
        ?string $classRoute = null

    ): PhpNamespace
    {
        if (empty($namespace)) {
            $namespace = 'App\\Controller';
        }

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
        // this is for entities only
//            ->addImplement(RouteParametersInterface::class) // will be simplified to A
//            ->addTrait(RouteParametersTrait::class); // will be simplified to AliasedClass

        return $namespace;

    }


    public function addMethod(ClassType $class,
                              string    $routeName, // 'app_do_something'
                              ?string    $templateName = null,
                              ?string   $route = null, // '/do-something'
                              ?string    $methodName = null, // function doSomething()
                              bool      $security = false // IsGranted('ROLE_ADMIN')
    )
    {
        if (empty($methodName)) {
            $methodName = u($routeName)->snake()->toString();
        }

        if (empty($route)) {
            $route = "/$routeName";
        }

        if (empty($templateName)) {
            $templatePrefix = u($class->getName())->replace('Controller', '')->lower();
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

        $method = $class->addMethod('__construct');
        // @todo: add DI, e.g. doctrine, entity repos, etc.
//        $method->addPromotedParameter('name', null)
//            ->addAttribute(Autowire::class)
//            ->setType('string')
//            ->setNullable(true);
//        $method->addPromotedParameter('args', [])
//            ->setPrivate();

        $method = $class->addMethod($methodName);
        if ($templateName) {
            $method
                ->addAttribute(Template::class, [$templateName]);
        }
        $method
            ->addAttribute(Route::class, ['path' => $route, 'name' => $routeName])
            ->setReturnType(Type::union(Type::Array, Response::class));
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


