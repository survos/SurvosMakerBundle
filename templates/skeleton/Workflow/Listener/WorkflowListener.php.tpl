<?= "<?php\n" ?>

<?php use function Symfony\Component\String\u; ?>


namespace App\Workflow\Listener;

use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use <?= $entity_full_class_name ?>;

/**
 * See all possible events in Symfony\Component\Workflow\Workflow
 *
 * Symfony\Component\Workflow\Event\GuardEvent
 * state_machine.guard
 * state_machine.{workflow_name}.guard
 * state_machine.{workflow_name}.guard.{transition_name}
 *
 * Symfony\Component\Workflow\Event\Event
 * state_machine.transition #before transition
 * state_machine.{workflow_name}.transition
 * state_machine.{workflow_name}.transition.{transition_name}
 * state_machine.enter
 * state_machine.{workflow_name}.enter
 * state_machine.{workflow_name}.enter.{place_name}
 * state_machine.{workflow_name}.announce.{transition_name}
 * state_machine.leave
 * state_machine.{workflow_name}.leave.{place_name}
 */
class <?= $shortClassName ?> implements EventSubscriberInterface
{

public function __construct(private LoggerInterface $logger) {

}

private <?=  $entityName ?> $entity;
    public function onGuard(GuardEvent $event)
    {
        $transition = $event->getTransition();

        /** @var <?= $entityName ?> */ $entity = $event->getSubject();
        $marking = $event->getMarking();
        $this->logger->info("onGuard", [$entity, $transition, $marking]);
//        $event->setBlocked(true);
    }

    public function onTransition(Event $event)
    {
        /** @var <?= $entityName ?> */ $entity = $event->getSubject();
        $transition = $event->getTransition();
        $marking = $event->getMarking();
        $this->logger->info("onTransition", [$entity, $transition, $marking]);
dd($transition, $entity);

    }

    public function onEnterPlace(Event $event)
    {
        $entity = $event->getSubject();
        $transition = $event->getTransition();
        $marking = $event->getMarking();
    }

public function onComplete(Event $event)
{
/** @var <?= $entityName ?> */ $entity = $event->getSubject();
$transition = $event->getTransition();
$marking = $event->getMarking();
}

    public function onLeavePlace(Event $event)
    {
        $entity = $event->getSubject();
        $transition = $event->getTransition();
        $marking = $event->getMarking();
    }

    public static function getSubscribedEvents(): array
    {

<?php $eventCodes = ['guard','transition','complete']; ?>

return [
        <?php foreach ($eventCodes as $eventSuffix) { ?>
'<?= sprintf('workflow.%s.%s', $workflowName, $eventSuffix) ?>' => 'on<?= u($eventSuffix)->title()->ascii() ?>',
<?php foreach ($constantsMap as $transitionName => $transitionConstant) { ?>
            '<?= sprintf('workflow.%s.%s.', $workflowName, $eventSuffix) ?>'  . <?= $entityName ?>::<?= $transitionConstant ?> => 'on<?= u($eventSuffix)->title()->ascii() ?>',
        <?php } ?>

    <?php } ?>
];
}

}
