<?php

namespace App\Menu;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Umbrella\AdminBundle\Menu\BaseAdminMenu;
use Umbrella\AdminBundle\UmbrellaAdminConfiguration;
use Umbrella\CoreBundle\Menu\Builder\MenuBuilder;
use Umbrella\CoreBundle\Menu\Builder\MenuItemBuilder;
use Umbrella\CoreBundle\Menu\DTO\MenuItem;
use function Symfony\Component\String\u;

class AdminMenu extends BaseAdminMenu
{

    public function __construct(private AuthorizationCheckerInterface $security,
                                protected Environment $twig,
                                protected UmbrellaAdminConfiguration $configuration,
                                private WorkflowHelperService $workflowHelper,
                                private JurisdictionRepository $jurisdictionRepository,
                                private BillRepository $billRepository,
                                RequestStack $requestStack)
    {
        parent::__construct($this->twig, $configuration);
    }


    public function addMenuItem(MenuItemBuilder $menu, $options): MenuItemBuilder
    {
        $options = $this->menuOptions($options);

//        dd($options);
        $item = $menu->add($options['id'])
            ->label($options['label'])
            ->icon('uil-table');

        if ($options['route']) {
            $item
                ->route($options['route'], $options['routeParameters'] ?? []);
        }

        $item
            ->end();
        return $item;

    }



    public function buildMenu(MenuBuilder $builder, array $options)
    {
        $u = $builder->root();

        // _docs/html/

//        $u = $r->add('umbrella');

        $u->add('about')
            ->icon('mdi mdi-lifebuoy')
            ->route('app_homepage');


        $formMenu = $u->add('extras')
            ->icon('uil-document-layout-center');

        $formMenu
            ->add('basic')
            ->route('app_homepage')
            ->end();

        $formMenu
            ->add('menu_demo')
            ->route('menu_demo')
            ->end();

        $formMenu
            ->add('blank')
            ->route('app_blank_page')
            ->end();


        $menu = $u;

        $this->addMenuItem($menu, ['route' => 'app_homepage', 'label' => "Home", 'icon' => 'fas fa-home']);
        return;

    }


}
