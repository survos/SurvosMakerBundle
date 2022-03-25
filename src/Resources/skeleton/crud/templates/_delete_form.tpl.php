<form method="post" action="{{ path('<?= $route_name ?>_delete', <?= $entity_twig_var_singular ?>.rp ) }}" onsubmit="return confirm('Are you sure you want to delete this <?= $entity_twig_var_singular ?> ?');">
    <input type="hidden" name="_method" value="DELETE">
    <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ <?= $entity_twig_var_singular ?>.<?= $entity_identifier ?>) }}">
    <button class="btn btn-danger">Delete</button>
</form>
