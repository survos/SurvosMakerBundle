<?php

namespace Survos\BaseBundle\DependencyInjection;

use Survos\BaseBundle\Configuration\SurvosBaseConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SurvosBaseExtension extends Extension
{

    // src/Acme/SocialBundle/DependencyInjection/AcmeSocialExtension.php
    public function load(array $configs, ContainerBuilder $container)
    {
        // this loads the services definitions from the xml,
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        // likely these can be combined.  This gets the menus
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new SurvosBaseConfiguration();

        $config = $this->processConfiguration($configuration, $configs);

        // you now have these 2 config keys
        // $config['twitter']['client_id'] and $config['twitter']['client_secret']
    }

    public function OLDload(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        assert($configuration, "configuration not returned");

        $config = $this->processConfiguration($configuration, $configs);
        assert($config, "config not returned.");
        $definition = $container->getDefinition('survos_base_bundle.base_service');
        $definition->setArgument(0, $config['routes']);


        // likely these can be combined
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

//        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config/container'));
//        $loader->load('knp-menu.yml');

        /* @todo: add menu items based on what bundles are installed (EasyAdminBundle, etc.) */
        $bundles = $container->getParameter('kernel.bundles');

        foreach (['KnpUOAuth2ClientBundle'] as $bundleName) {
            if (!isset($bundles[$bundleName])) {
                throw new \InvalidArgumentException(
                    "The bundle $bundleName needs to be registered in order to use ".  __CLASS__
                );
                $def = $container->findDefinition('oauth2.registry');
                dd($def, $container);
            }
        }

        // dd($bundles); die();

        // $configManager = $container->get('easyadmin.config.manager');
        // $definition->setArgument(1, $configManager);
        // $definition->setArgument(1, $config['min_sunshine']);
    }

    public function getAlias(): string
    {
        return 'survos_base';
    }
}
