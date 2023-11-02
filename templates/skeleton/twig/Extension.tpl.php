<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements ?>

class <?= $class_name ?> extends AbstractExtension
{

    public function __construct(private array $config) {
    }

    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, add ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('filter_name', fn(string $s) => '@todo: filter ' . $s),
        ];
    }

    public function getFunctions(): array
    {
        return [
//            new TwigFunction('function_name', [<?= $runtime_class_name??'' ?>::class, 'doSomething']),
        ];
    }
}
