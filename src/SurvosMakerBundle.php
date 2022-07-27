<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Survos\Bundle\MakerBundle;

use Survos\Bundle\MakerBundle\DependencyInjection\Compiler\SurvosMakerCompilerPass;
use Survos\Bundle\MakerBundle\Maker\MakeBundle;
use Survos\Bundle\MakerBundle\Maker\MakeCrud;
use Survos\Bundle\MakerBundle\Maker\MakeMenu;
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
    // src/Acme/HelloBundle/DependencyInjection/AcmeHelloExtension.php
    public function prepend(ContainerBuilder $container)
    {

        // get all bundles
        $bundles = $container->getParameter('kernel.bundles');
        dd($bundles, $container->getExtensions(), __FILE__, __LINE__);
        // determine if AcmeGoodbyeBundle is registered
        if (!isset($bundles['AcmeGoodbyeBundle'])) {
            // disable AcmeGoodbyeBundle in bundles
            $config = ['use_acme_goodbye' => false];
            foreach ($container->getExtensions() as $name => $extension) {
                switch ($name) {
                    case 'acme_something':
                    case 'acme_other':
                        // set use_acme_goodbye to false in the config of
                        // acme_something and acme_other
                        //
                        // note that if the user manually configured
                        // use_acme_goodbye to true in config/services.yaml
                        // then the setting would in the end be true and not false
                        $container->prependExtensionConfig($name, $config);
                        break;
                }
            }
        }

        // get the configuration of AcmeHelloExtension (it's a list of configuration)
//        dd($configs);

        // iterate in reverse to preserve the original order after prepending the config
        foreach (array_reverse($configs) as $config) {
            // check if entity_manager_name is set in the "acme_hello" configuration
            if (isset($config['entity_manager_name'])) {
                // prepend the acme_something settings with the entity_manager_name
                $container->prependExtensionConfig('acme_something', [
                    'entity_manager_name' => $config['entity_manager_name'],
                ]);
            }
        }
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
//        $builder->register('survos_maker.generator', Generator::class);
//        dd($builder->getParameterBag()->all(), $container->parameters());

//        $this->prepend($builder);
//        $ns = $container->services()->get('maker.root_namespace');
//        dd($ns);

        // prepend
//        $builder->prependExtensionConfig('maker', [
//            'root_namespace' => 'foo/bar',
//        ]);

//        $ns = $builder->getParameter('maker.root_namespace', $config['template_path']); dd($ns, __FILE__);

        foreach ([MakeMenu::class, MakeService::class] as $makerClass) {
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
//        dump($config, __FILE__);


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
//              ->booleanNode('unicorns_are_real')->defaultTrue()->end()
//            ->integerNode('min_sunshine')->defaultValue(3)->end()
            ->end();
    }

// https://stackoverflow.com/questions/72507212/symfony-6-1-get-another-bundle-configuration-data/72664468#72664468
    public function XXprependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $configs = $builder->getExtensionConfig('MakerBundle');
        dd($configs);

        $builder->get('maker');
        $definition = $container->services()->get('maker');
        dd($definition);
        // prepend
        $builder->prependExtensionConfig('framework', [
            'cache' => ['prefix_seed' => 'foo/bar'],
        ]);

        // append
        $container->extension('framework', [
            'cache' => ['prefix_seed' => 'foo/bar'],
        ]);

        dd($builder->getParameterBag()->all());

//        // append from file
//        $container->import('../config/packages/cache.php');
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
