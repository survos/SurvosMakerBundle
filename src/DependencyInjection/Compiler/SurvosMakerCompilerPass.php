<?php

namespace Survos\Bundle\MakerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SurvosMakerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
//        dump($container->getParameterBag()->all(), $container->getParameter('kernel.bundles'));
//        $definition = $container->getDefinition('maker');
//        dd($definition); die('stopped' . __FILE__);
//        $definition->setArgument(1, new Reference('oauth2.registry'));
    }
}


