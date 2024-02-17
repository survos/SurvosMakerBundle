<?= "<?php\n" ?>

<?php $entity_identifier = $entity_var_singular . 'Id'; ?>

// uses Survos Param Converter, from the UniqueIdentifiers method of the entity.

namespace <?= $namespace ?>;

use <?= $entity_full_class_name ?>;
use <?= $form_full_class_name ?>;
use Doctrine\ORM\EntityManagerInterface;
<?php if (isset($repository_full_class_name)): ?>
use <?= $repository_full_class_name ?>;
<?php endif ?>
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Survos\ApiGrid\Components\ApiGridComponent;
// @todo: if Workflow Bundle active
use Survos\WorkflowBundle\Traits\HandleTransitionsTrait;

#[Route('<?= $route_path ?>')]
class <?= $class_name ?> extends AbstractController<?= "\n" ?>
{

use HandleTransitionsTrait;

public function __construct(
private EntityManagerInterface $entityManager,
private ApiGridComponent $apiGridComponent,
private ?IriConverterInterface $iriConverter=null
) {
}

#[Route(path: '/browse/', name: '<?= $route_name ?>_browse', methods: ['GET'])]
#[Route('/index', name: '<?= $route_name ?>_index')]
public function browse<?= $entity_twig_var_singular ?>(Request $request): Response
{
$class = <?= $entity_class_name ?>::class;
$shortClass = '<?= $entity_class_name ?>';
$useMeili = $request->get('_route') == 'app_browse';
// this should be from inspection bundle!
$apiCall = $useMeili
? '/api/meili/' . $shortClass
: $this->iriConverter->getIriFromResource($class, operation: new GetCollection(),
context: $context??[])
;

$this->apiGridComponent->setClass($class);
$c = $this->apiGridComponent->getDefaultColumns();
$columns = array_values($c);
$useMeili = '<?= $route_name ?>_browse' == $request->get('_route');
// this should be from inspection bundle!
$apiCall = $useMeili
? '/api/meili/'.$shortClass
: $this->iriConverter->getIriFromResource($class, operation: new GetCollection(),
context: $context ?? [])
;

return $this->render('<?= $templates_path ?>/browse.html.twig', [
'class' => $class,
'useMeili' => $useMeili,
'apiCall' => $apiCall,
'columns' => $columns,
'filter' => [],
]);
}

#[Route('/symfony_crud_index', name: '<?= $route_name ?>_symfony_crud_index')]
    public function symfony_crud_index(<?= $repository_class_name ?> $<?= $repository_var ?>): Response
    {
        return $this->render('<?= $templates_path ?>/index.html.twig', [
            '<?= $entity_twig_var_plural ?>' => $<?= $repository_var ?>->findBy([], [], 30),
        ]);
    }

#[Route('<?= $route_name ?>/new', name: '<?= $route_name ?>_new')]
    public function new(Request $request): Response
    {
        $<?= $entity_var_singular ?> = new <?= $entity_class_name ?>();
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->entityManager;
            $entityManager->persist($<?= $entity_var_singular ?>);
            $entityManager->flush();

            return $this->redirectToRoute('<?= $route_name ?>_index');
        }

        return $this->render('<?= $templates_path ?>/new.html.twig', [
            '<?= $entity_twig_var_singular ?>' => $<?= $entity_var_singular ?>,
            'form' => $form->createView(),
        ]);
    }
}
