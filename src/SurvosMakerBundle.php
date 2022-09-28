<?php

/*
 * This file is based on the Symfony MakerBundle package.
 */

namespace Survos\Bundle\MakerBundle;

use Survos\Bundle\MakerBundle\DependencyInjection\Compiler\SurvosMakerCompilerPass;
use Survos\Bundle\MakerBundle\Maker\MakeBundle;
use Survos\Bundle\MakerBundle\Maker\MakeCrud;
use Survos\Bundle\MakerBundle\Maker\MakeInvokableCommand;
use Survos\Bundle\MakerBundle\Maker\MakeMenu;
use Survos\Bundle\MakerBundle\Maker\MakeModel;
use Survos\Bundle\MakerBundle\Maker\MakeParamConverter;
use Survos\Bundle\MakerBundle\Maker\MakeService;
use Survos\Bundle\MakerBundle\Maker\MakeWorkflow;
use Survos\Bundle\MakerBundle\Maker\MakeWorkflowListener;
use Survos\Bundle\MakerBundle\Renderer\ParamConverterRenderer;
//use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\DoctrineAttributesCheckPass;
use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\MakeCommandRegistrationPass;
use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\RemoveMissingParametersPass;
use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\SetDoctrineAnnotatedPrefixesPass;
//use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\SetDoctrineManagerRegistryClassPass;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SurvosMakerBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        foreach ([MakeMenu::class, MakeService::class, MakeInvokableCommand::class, MakeModel::class] as $makerClass) {
            $builder->autowire($makerClass)
                ->addTag(MakeCommandRegistrationPass::MAKER_TAG) // 'maker.command'
                ->addArgument(new Reference('maker.generator'))
                ->addArgument($config['template_path'])
            ;
        }
        $builder->autowire(MakeBundle::class)
            ->addTag(MakeCommandRegistrationPass::MAKER_TAG) // 'maker.command'
            ->addArgument(new Reference('maker.generator'))
            ->addArgument($config['template_path'])
            ->addArgument($config['vendor'])
            ->addArgument($config['relative_bundle_path'])
            ->addArgument($config['bundle_name'])
        ;

        // we can likely combine these, or even move it to crud
        $builder->register('maker.param_converter_renderer', ParamConverterRenderer::class)
            ->addArgument(new Reference('maker.generator'))
            ->addArgument($config['template_path']);

        $builder->autowire(MakeParamConverter::class)
            ->addTag('maker.command')
            ->addArgument(new Reference('maker.doctrine_helper'))
//                ->addArgument(new Reference('maker.generator'))
            ->addArgument(new Reference('maker.param_converter_renderer'))
            ->addArgument($config['template_path'])
            ->addArgument(new Reference('parameter_bag'))

        ;

        $builder->autowire(MakeCrud::class)
            ->addTag('maker.command')
            ->addArgument(new Reference('maker.doctrine_helper'))
            ->addArgument(new Reference('maker.renderer.form_type_renderer'))
        ;

        $builder->autowire(MakeWorkflowListener::class)
            ->addTag('maker.command')
            ->addArgument(new Reference('maker.doctrine_helper'))
            ->addArgument(new Reference('maker.generator'))
            ->addArgument(new Reference('workflow.registry'))
        ;

        $builder->autowire(MakeWorkflow::class)
            ->addTag('maker.command')
            ->addArgument(new Reference('maker.doctrine_helper'))
            ->addArgument($config['template_path'])
        ;
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('template_path')->defaultValue(__DIR__ . '/../templates/skeleton/')->end()
            ->scalarNode('vendor')->defaultValue('Survos')->end()
            ->scalarNode('bundle_name')->defaultValue('FooBundle')->end()
            ->scalarNode('relative_bundle_path')->defaultValue('lib/temp/src')->end()
            ->end();
    }

    public function build(ContainerBuilder $container)
    {
        // add a priority so we run before the core command pass
        //        $container->addCompilerPass(new DoctrineAttributesCheckPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 11);
        $container->addCompilerPass(new MakeCommandRegistrationPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        $container->addCompilerPass(new RemoveMissingParametersPass());
        //        $container->addCompilerPass(new SetDoctrineManagerRegistryClassPass());
        $container->addCompilerPass(new SetDoctrineAnnotatedPrefixesPass());

        //        dump(__FILE__, __LINE__);
        //        $container->addCompilerPass(new SurvosMakerCompilerPass());
    }
}
