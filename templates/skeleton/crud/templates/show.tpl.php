{% extends "<?= $route_name ?>/base.html.twig" %}

{% block body %}
    <h1><?= $entity_class_name ?></h1>

    <table class="table">
        <tbody>
<?php foreach ($entity_fields as $field): ?>
            <tr>
                <th><?= ucfirst($field['fieldName']) ?></th>
                <td>{{ <?= $helper->getEntityFieldPrintCode($entity_twig_var_singular, $field) ?> }}</td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>

{% if is_granted('ROLE_ADMIN') %}
    <a class="btn btn-primary" href="{{ path('<?= $route_name ?>_edit', <?= $entity_twig_var_singular ?>.rp) }}">edit</a>
    {{ include('<?= $route_name ?>/_delete_form.html.twig') }}
{% endif %}
{% endblock %}
