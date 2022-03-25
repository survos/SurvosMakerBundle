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
// use Symfony\Bundle\FrameworkBundle\Controller\<?= $parent_class_name ?>;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("<?= $route_path ?>")
 */
class <?= $class_name ?> extends <?= $parent_class_name; ?><?= "\n" ?>
{

public function __construct(private EntityManagerInterface $entityManager) {

}

#[Route('/', name: '<?= $route_name ?>_index')]
<?php if (isset($repository_full_class_name)): ?>
    public function index(<?= $repository_class_name ?> $<?= $repository_var ?>): Response
    {
        return $this->render('<?= $templates_path ?>/index.html.twig', [
            '<?= $entity_twig_var_plural ?>' => $<?= $repository_var ?>->findBy([], [], 30),
        ]);
    }
<?php else: ?>
    public function index(): Response
    {
        $<?= $entity_var_plural ?> = $this->getDoctrine()
            ->getRepository(<?= $entity_class_name ?>::class)
            ->findAll();

        return $this->render('<?= $templates_path ?>/index.html.twig', [
            '<?= $entity_twig_var_plural ?>' => $<?= $entity_var_plural ?>,
        ]);
    }
<?php endif ?>

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
