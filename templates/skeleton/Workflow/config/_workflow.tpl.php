<?= "<?php\n" ?>

use function Symfony\Component\String\u;
use Symfony\Config\FrameworkConfig;

use <?= $entity_full_class_name ?>;


<?php $initialPlace = $places[0]; ?>
return static function (FrameworkConfig $framework) {
    $tracking = $framework->workflows()->workflows('<?= strtolower($entityName) ?>');
    $tracking
        ->type('state_machine') // or 'state_machine'
        ->supports([<?= $entityName ?>::class])
        ->initialMarking([<?= $entityName ?>::<?= $initialPlace ?>]);

    $tracking->auditTrail()->enabled(true);
    $tracking->markingStore()
        ->type('method')
        ->property('marking');

    <?php foreach ($places as $place) { ?>
        $tracking->place()->name(<?= $entityName ?>::<?= $place ?>);
    <?php } ?>

    <?php foreach ($transitions as $idx => $transition) { ?>
    $tracking->transition()
        ->name(<?= $entityName ?>::<?= $transition ?>)
        ->from([<?= $entityName ?>::<?= $initialPlace ?>])
        ->to([<?= $entityName ?>::<?= $places[$idx % count($places)] ?>]);
    <?php } ?>


};
