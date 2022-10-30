<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>


class <?= $class_name ?> extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // $builder->setParameter('survos_workflow.direction', $config['direction']);

        // twig classes

/*
$definition = $builder
->autowire('survos.barcode_twig', BarcodeTwigExtension::class)
->addTag('twig.extension');

$definition->setArgument('$widthFactor', $config['widthFactor']);
$definition->setArgument('$height', $config['height']);
$definition->setArgument('$foregroundColor', $config['foregroundColor']);
*/

    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('direction')->defaultValue('LR')->end()
            ->scalarNode('base_layout')->defaultValue('base.html.twig')->end()
            ->arrayNode('entities')
            ->scalarPrototype()
            ->end()->end()
            ->booleanNode('enabled')->defaultTrue()->end()
//            ->integerNode('min_sunshine')->defaultValue(3)->end()
            ->end();
    }

}
