<?php

declare(strict_types=1);

namespace Survos\Bundle\MakerBundle\Service;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Twig\Environment;

use function Symfony\Component\String\u;

class MakerService
{
    //    private PropertyAccessor $propertyAccessor;
    public function __construct(
        private Environment $twig,
        private PropertyAccessorInterface $propertyAccessor
    ) {
        //        $this->propertyAccessor = new PropertyAccessor();
    }


    /**
     * Get a list of classes that are not in the use section of the class
     */
    public function getNewUses(string $className, array $classList): array
    {
        $existingUses = $this->getClassUses($className);
        return array_diff($classList, $existingUses);
    }

    /**
     * @param string $fqcn
     * @return array<int,string>
     */
    public function getClassUses(?string $fqcn = null, ?ReflectionClass $reflectionClass = null): array
    {
        if (!$reflectionClass) {
            $reflectionClass = $this->getReflectionClass($fqcn);
        }
        $uses = [];
        foreach ($reflectionClass->getDeclaringNamespaceAst()->stmts as $stmt) {
            foreach ($stmt->uses ?? [] as $use) {
                $uses[] = join('\\', $use->name->parts);
            }
        }
        return $uses;
    }

    public function getReflectionClass(string $fqcn): ReflectionClass
    {
        assert(class_exists($fqcn), $fqcn . " is not loaded");
        return (new BetterReflection()) // $this->betterReflection
            ->reflector()
            ->reflectClass($fqcn);
    }

    public function modifyClass(
        ReflectionClass $reflectionClass,
        array $traits = [],
        array $uses = [],
        array $implements = [],
        array $injects = [],
        string $methodName = null,
        string $php = null,
    ): string {
        $source = $reflectionClass->getLocatedSource()->getSource();
        // go through inject to separate typehint and parameter
        foreach ($injects as $inject) {
            // actually, because of autowiring this needs work.  For now, just the very simple implements
            if (str_contains($inject, '$')) {
                [$class, $var] = explode('$', $inject);
            } else {
                $class = $inject;
                $var = null;
            }
            $class = trim($class);

            if (str_starts_with($class, '?')) {
                $class = str_replace('$', '', $class);
                $optional = true;
            } else {
                $optional = false;
            }
            //            assert(class_exists($class), "[$class]");
            $shortClass = (new \ReflectionClass($class))->getShortName();
            if (!$var) {
                $var = u($shortClass)->camel()->replace('Interface', '')->toString();
            }

            array_push($uses, $class);
            $injectionParams[] = [
                'class' => $class,
                'shortClass' => $shortClass,
                'var' => $var,
                'optional' => $optional,
            ];
        }

        $params = [];

        // find the constructor
        $injectMethod = '__construct';
        if ($reflectionClass->hasMethod('__invoke')) {
            $injectMethod = '__invoke';
        }
        if ($reflectionClass->hasMethod($injectMethod)) {
            $method = $reflectionClass->getMethod($injectMethod);
            $existingParams = array_map(fn (ReflectionParameter $param) => $param->getName(), $method->getParameters());

            foreach ($injectionParams as $injectionParam) {
                $var = $injectionParam['var'];
                if (!in_array($var, $existingParams)) {
                    $paramStr = sprintf('%s%s $%s', $injectionParam['optional'] ? '?' : '', $injectionParam['shortClass'], $var);
                    $params[] = $paramStr;
                    //                    dd($injectionParam, $existingParams);
                }
            }

            //            foreach ($method->getParameters() as $parameter) {
            //                dump($parameter);
            //            }


            //            dd($method->getParameters());
            //            $constructorSource = join("\n", $this->getSourceLines($method->getLocatedSource()->getSource(), $method->getStartLine(), $method->getEndLine()));

            // we could recreate the contructor header, so that we can put the new parameters at the end (in case named parameters are being used.)
            // hack for just, force the params in
            $hasExisting = $method->getNumberOfParameters();
            $newPhp = count($params) ? join(',', $params) . ($hasExisting ? ',' : '') : '';
            // problematic if inject and replacing the method body is the same

            // get the original body
        }

        // if we've passed a method, then only replace the body, otherwise add the new php code to the end of the class.
        if ($methodName) {
            // start with the modified source, if we've injected parameters
            $originalClass = $reflectionClass->getName();
            $astLocator = (new BetterReflection())->astLocator();
            $reflector = new DefaultReflector(new StringSourceLocator($source, $astLocator));
            //            $reflectionClass = $reflector->reflectClass($originalClass);

            //            $reflectionClass = (new BetterReflection())->reflector()->reflectClass()
            $method = $reflectionClass->getMethod($methodName);
            $originalBody = $method->getBodyCode();
            $methodSource = $this->getMethodSource($reflectionClass, $methodName);

            // too complicated...
            $newMethodSource = $this->twig->render('@SurvosMaker/skeleton/class/_method.php.twig', [
                'params' => $method->getParameters(),
                'methodName' => $methodName,
                'returnType' => $method->getReturnType(),
            ]);

            // hack
            if ($php) {
                $returnType = $method->getReturnType();
                $divider = ": $returnType\s*{";
                if (!preg_match("/$divider/", $methodSource)) {
                    dd($divider, $methodSource);
                }
                assert(preg_match("/$divider/", $methodSource), "$divider not found in $methodSource");
                $newMethodSource = preg_replace("/$divider.*?}/sm", ": $returnType { \n" . $php . "\n}\n", $methodSource);
                //            $newMethodSource = str_replace($originalBody, $php, $methodSource);

                //            dd($newMethodSource, $methodSource, $method->getReturnType(), $method->getName());
                // remove the old method

                // we could remove the method completely, then add the new one,
                // https://tomasvotruba.com/blog/2017/11/06/how-to-change-php-code-with-abstract-syntax-tree/?ref=aggregate.stitcher.io
                $source = $this->replaceLines($source, $method->getStartLine(), $method->getEndLine(), $newMethodSource);
                //                dd($methodName, $php, $originalBody, $source);
            }
            //            dd($source);
        }

        // after the new method body is in place, add the injection
        $source = str_replace($injectMethod . '(', $injectMethod . '(' . $newPhp, $source);

        if (!$constructorSource = $this->getMethodSource($reflectionClass, '__construct')) {
            assert(false, "@todo: create constructor");
        }

        $uses = array_unique(array_merge($uses, $traits, $implements));

        // first, get the new uses statements based on traits, etc.  @todo
        //        $reflectionClass = $this->getReflectionClass($fqcn);

        $shortClassName = $reflectionClass->getShortName();
        $newImplements = array_diff($implements, $reflectionClass->getInterfaceNames());

        // hackish, AST would be more better, but regex will have to do.
        //        dd($reflectionClass->getInterfaceNames());
        //        dd($newImplements);
        //
        //        dd($reflectionClass->getImmediateInterfaces());

        if (count($newImplements)) {
            $php = join(",", array_map(fn ($c) => (new \ReflectionClass($c))->getShortName(), $newImplements));
            dd($php);
            // if this is in a comment, it may fail
            if (str_contains($source, $iString = "class $shortClassName implements ")) {
                $source = str_replace($iString, $iString . $php . ',', $source);
            // add new implements
            } else {
                $toReplace = "class $shortClassName ";
                assert(str_contains($source, $toReplace), "missing $toReplace in source");
                $source = str_replace($toReplace, "class $shortClassName implements " . $php, $source);
            }
        }

        //        $classSignature = $this->getSourceLines($source, $reflectionClass->getStartLine(), $reflectionClass->getEndLine());
        //        dd($classSignature);
        // insert traits before uses, so that we have an accurate line number
        $newTraits = array_diff($traits, $reflectionClass->getTraitNames());
        if (count($newTraits)) {
            // add them to the END of the class, let ecs fix them.
            assert(preg_match('/}$/', $source), "the last line of the source must be a } to close the class.");
            $phpLines = array_map(fn (string $useClass) => sprintf("use %s;", (new \ReflectionClass($useClass))->getShortName()), $newTraits);
            $source = preg_replace('/}$/', join("\n", $phpLines) . "\n}", $source);
        }

        $existingUses = $this->getClassUses(reflectionClass: $reflectionClass); // odd, getTraits exists, why not getUses()?
        $newUses = array_diff($uses, $existingUses);
        if (count($newUses)) {
            $php = array_map(fn (string $useClass) => sprintf("use %s;", (new \ReflectionClass($useClass))->getName()), $newUses);
            $sourceLines = explode("\n", $source);
            array_splice($sourceLines, $reflectionClass->getStartLine() - 1, 0, $php);
            $source = join("\n", $sourceLines);
        }

        return $source;
    }

    private function replaceLines(string $source, int $startLine, int $endLine, string $newContent)
    {
        $lines = explode("\n", $source);
        array_splice($lines, $startLine - 1, $endLine - $startLine + 1, explode("\n", $newContent));
        return join("\n", $lines);
    }

    private function getSourceLines(string $source, $startLine, $endLine): array
    {
        $sourceLines = explode("\n", $source);
        return array_slice($sourceLines, $startLine - 1, $endLine - $startLine + 1);
    }

    public function insertTraits(string $fqcn, array $traits): string
    {
        $classInfo = $this->getReflectionClass($fqcn);
        $newTraits = array_diff($traits, $classInfo->getTraitNames());
        $source = $classInfo->getLocatedSource()->getSource();

        // add them to the END of the class, let ecs fix them.
        assert(preg_match('/}$/', $source), "the last line of the source must be a } to close the class.");
        $newSource = preg_replace('/}$/', join("\n", array_map(fn ($traitName) => "use $traitName;", $newTraits)) . "\n}", $source);
        dump($classInfo->getTraitNames(), $newTraits, $traits, $newSource);

        //        $source = $classInfo->getLocatedSource()->getSource();

        dd($classInfo->getAttributes(), $classInfo->getStartLine(), $this->getSourceLines($classInfo->getFileName(), $classInfo->getStartLine(), $classInfo->getEndLine()));

        // traits go after the class opening.
        foreach ($classInfo->getTraits() as $reflectionTrait) {
            dd($reflectionTrait);
        }
        $classInfo->getTraitNames();
        $source = $classInfo->getLocatedSource()->getSource();
        $sourceLines = explode("\n", $source);
        dd($traits, $sourceLines);
        // insert the uses after the last existing one.
        // @todo: handle no uses, although somewhat rare
        array_splice(
            $sourceLines,
            $this->getLastUseLineNumber(reflectionClass: $classInfo),
            0,
            array_map(fn (string $useClass) => "use $useClass;", $uses)
        );
        dd($sourceLines);
        return join("\n", $sourceLines);
    }

    private function getProperty(mixed $object, string $propery): mixed
    {
        if (!$this->propertyAccessor->isReadable($object, $propery)) {
            dd($propery, $object);
        }
        return $this->propertyAccessor->getValue($object, $propery);
    }

    public function getMethodSource(ReflectionClass $reflectionClass, string $methodName): ?string
    {
        // use the PHP reflection, not BetterReflection?
        //        $reflectionClass = new \ReflectionClass($fqcn);
        //        $reflectionMethod = $reflectionClass->getMethod($methodName);
        //        $methodSource = $this->getSourceLines($reflectionMethod->getFileName(), $reflectionMethod->getStartLine(), $reflectionMethod->getEndLine());
        //        dump($reflectionMethod->getAttributes(), $methodSource);
        //
        //        return join("\n", $methodSource);
        //        dump($fqcn, $methodName, $methodSource);
        if ($reflectionClass->hasMethod($methodName)) {
            $method = $reflectionClass->getMethod($methodName);
            return join("\n", $this->getSourceLines($method->getLocatedSource()->getSource(), $method->getStartLine(), $method->getEndLine()));
            //            dd($method, $method->getStartLine(), ));
            dd($method->getNumberOfParameters(), $this->getProperty($method, 'methodNode'));
            dd($method, $method->getLocatedSource());
        } else {
            assert(false, $methodName . ' does not exist in ' . $reflectionClass->getName());
            return null;
        }
    }

    /**
     * @param string $fqcn
     * @return array<int,string>
     */
    public function getLastUseLineNumber(?string $fqcn = null, ?ReflectionClass $reflectionClass = null): ?int
    {
        $lastLineNumber = null;
        if (!$reflectionClass) {
            $reflectionClass = $this->getReflectionClass($fqcn);
        }
        $uses = [];
        $accessor = new PropertyAccessor();
        foreach ($reflectionClass->getDeclaringNamespaceAst()->stmts as $stmt) {
            foreach ($stmt->uses ?? [] as $use) {
                $attributes = $accessor->getValue($use, 'attributes');
                //                dd($use, $attributes??null);
                $lastLineNumber = $attributes['endLine'];
                $uses[] = join('\\', $use->name->parts);
            }
        }
        return $lastLineNumber;
    }
}
