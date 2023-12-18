{% extends "<?= $route_name ?>/base.html.twig" %}

{% block title %}{{  class }}{% endblock %}

{% block body %}
<h1>{{ class }} {{ _self }}</h1>

<twig:api_grid
        facets="false"
        :class="class"
        :apiGetCollectionUrl="apiCall"
        :caller="_self"
        :columns="columns"
>

    <twig:block name="id">
        ID: {{ row.id }}
    </twig:block>
</twig:api_grid>
{% endblock %}
