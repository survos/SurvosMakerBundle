<?= $helper->getHeadPrintCode($entity_class_name.' index'); ?>


{% block body %}
    <h1><?= $entity_class_name ?> index</h1>

    {% include "<?= $entity_twig_var_singular ?>/_table.html.twig" %}

    <a class="btn btn-primary" href="{{ path('<?= $route_name ?>_new') }}"><span class="fas fa-plus"></span>New  <?= $entity_class_name ?></a>
{% endblock %}
