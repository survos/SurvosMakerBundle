<?php

namespace Survos\Bundle\MakerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SurvosMakerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
//        $definition = $container->getDefinition('survos_base_bundle.base_service');
//        $definition->setArgument(1, new Reference('oauth2.registry'));
    }
}


