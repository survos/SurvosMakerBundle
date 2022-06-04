<?= "<?php\n" ?>
declare(strict_types=1);

namespace <?= $namespace ?>;

use <?= $entity_full_class_name ?>;
<?php if (isset($repository_full_class_name)): ?>
    use <?= $repository_full_class_name ?>;
<?php endif ?>

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class <?= $shortClassName ?> implements ParamConverterInterface
{
    public function __construct(private ManagerRegistry $registry)
    {
    }

    /**
     * {@inheritdoc}
     *
     * Check, if object supported by our converter
     */
    public function supports(ParamConverter $configuration): bool
    {
        return <?= $entity_class_name ?>::class == $configuration->getClass();
    }

    /**
     * {@inheritdoc}
     *
     * Applies converting
     *
     * @throws \InvalidArgumentException When route attributes are missing
     * @throws NotFoundHttpException     When object not found
     * @throws Exception
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $params = $request->attributes->get('_route_params');

//        if (isset($params['<?= $entity_unique_name ?>']) && ($<?= $entity_unique_name ?> = $request->attributes->get('<?= $entity_unique_name ?>')))

        $<?= $entity_unique_name ?> = $request->attributes->get('<?= $entity_unique_name ?>');
        if ($<?= $entity_unique_name ?> === 'undefined') {
            throw new Exception("Invalid <?= $entity_unique_name ?> " . $<?= $entity_unique_name ?>);
        }

        // Check, if route attributes exists
        if (null === $<?= $entity_unique_name ?> ) {
            if (!isset($params['<?= $entity_unique_name ?>'])) {
                return false; // no <?= $entity_unique_name ?> in the route, so leave.  Could throw an exception.
            }
        }

        // Get actual entity manager for class.  We can also pass it in, but that won't work for the doctrine tree extension.
        $repository = $this->registry->getManagerForClass($configuration->getClass())?->getRepository($configuration->getClass());

        // Try to find the entity
        if (!$<?= $entity_var_name ?> = $repository->findOneBy(['id' => $<?= $entity_unique_name ?>])) {
            throw new NotFoundHttpException(sprintf('%s %s object not found.', $<?= $entity_unique_name ?>, $configuration->getClass()));
        }

        // Map found <?= $entity_var_name ?> to the route's parameter
        $request->attributes->set($configuration->getName(), $<?= $entity_var_name ?>);
        return true;
    }

}
