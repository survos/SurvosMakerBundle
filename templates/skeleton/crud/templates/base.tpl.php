<?= $helper->getHeadPrintCode('BASE '.$entity_class_name) ?>

{% block page_title %}{{ <?= $entity_twig_var_singular ?> }}{% endblock %}
{% block page_subtitle %}{{ app.request.get('_route') }}{% endblock %}

{% block body %}

If we have a <?php echo $entity_class_name ?> entity, we should display it here.

{% endblock %}
