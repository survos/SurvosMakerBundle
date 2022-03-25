<?php

namespace Survos\Bundle\MakerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SurvosMakerBundleExtension extends Extension
{

    // src/Acme/SocialBundle/DependencyInjection/AcmeSocialExtension.php
    public function load(array $configs, ContainerBuilder $container)
    {
        // this loads the services definitions from the xml,
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('makers.xml');
        dd($loader);

    }


    public function getAlias(): string
    {
        return 'survos_maker_bundle';
    }
}
