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

#[Route('<?= $route_path ?>/{<?= $entity_identifier ?>}')]
class <?= $class_name ?> extends <?= $parent_class_name; ?><?= "\n" ?>
{

public function __construct(private EntityManagerInterface $entityManager) {

}

#[Route(path: '/transition/{transition}', name: '<?= $entity_var_singular?>_transition')]
public function transition(Request $request, WorkflowInterface $<?= $entity_var_singular ?>StateMachine, string $transition, <?= $entity_class_name ?> $<?= $entity_var_singular ?>): Response
{
if ($transition === '_') {
$transition = $request->request->get('transition'); // the _ is a hack to display the form, @todo: cleanup
}

$this->handleTransitionButtons($repoStateMachine, $transition, $github);
$this->entityManager->flush(); // to save the marking
return $this->redirectToRoute('<?= $route_name ?>_show', $github->getRP());
}

#[Route('/', name: '<?= $route_name ?>_show', options: ['expose' => true])]
    public function show(<?= $entity_class_name ?> $<?= $entity_var_singular ?>): Response
    {
        return $this->render('<?= $templates_path ?>/show.html.twig', [
            '<?= $entity_twig_var_singular ?>' => $<?= $entity_var_singular ?>,
        ]);
    }

#[Route('/edit', name: '<?= $route_name ?>_edit', options: ['expose' => true])]
    public function edit(Request $request, <?= $entity_class_name ?> $<?= $entity_var_singular ?>): Response
    {
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('<?= $route_name ?>_index');
        }

        return $this->render('<?= $templates_path ?>/edit.html.twig', [
            '<?= $entity_twig_var_singular ?>' => $<?= $entity_var_singular ?>,
            'form' => $form->createView(),
        ]);
    }

#[Route('/delete', name: '<?= $route_name ?>_delete', methods:['DELETE'])]
    public function delete(Request $request, <?= $entity_class_name ?> $<?= $entity_var_singular ?>): Response
    {
        // hard-coded to getId, should be get parameter of uniqueIdentifiers()
        if ($this->isCsrfTokenValid('delete'.$<?= $entity_var_singular ?>->getId(), $request->request->get('_token'))) {
            $entityManager = $this->entityManager;
            $entityManager->remove($<?= $entity_var_singular ?>);
            $entityManager->flush();
        }

        return $this->redirectToRoute('<?= $route_name ?>_index');
    }
}
