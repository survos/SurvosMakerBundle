{% extends "<?= $route_name ?>/base.html.twig" %}

{% block title %}{{  class }}{% endblock %}

{% block body %}

{% import "@SurvosBase/macros/cards.html.twig" as card_widget %}

{% set _controller = 'videos' %}

<h3>Youtube Videos</h3>
<div {{ stimulus_controller(_controller, {
     class: class,
     api_call: api_route(class),
     sortableFields: sortable_fields(class),
     filter: filter,
     }) }}>

    {{ card_widget.entityTable({
    stimulusController: _controller
    }) }}
</div>


{% endblock %}
