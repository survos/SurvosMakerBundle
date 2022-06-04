{% extends "<?= $route_name ?>/base.html.twig" %}

{% block body %}
    <h1>Edit <?= $entity_class_name ?></h1>

    {{ include('<?= $route_name ?>/_form.html.twig', {'button_label': 'Update'}) }}
    {{ include('<?= $route_name ?>/_delete_form.html.twig') }}
{% endblock %}
