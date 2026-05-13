<?php

declare(strict_types=1);

namespace app\modules\Designer;

use app\enums\ApplicationError;
use app\enums\MenuItemTab;
use app\helpers\Application;
use app\models\ArwardsDataHelper;
use app\modules\Common\AbstractController;

class MenuItemController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function index()
    {
        $menuItems = $this->getNavItems($this->application->getConnectedUser()->person, true);
        $tab = MenuItemTab::tryFrom($this->flight->request()->query->tab ?? '') ?? MenuItemTab::Navbar;

        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isMenuDesigner(), __FILE__, __LINE__)) {
            $this->render('Designer/views/menuItem.latte', $this->getAllParams([
                'navbarItems'  => array_filter($menuItems, fn($i) => $i->What === 'navbar'),
                'sidebarItems' => array_filter($menuItems, fn($i) => $i->What === 'sidebar'),
                'groups'       => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'page'         => $this->application->getConnectedUser()->getPage(),
                'activeTab'    => $tab,
                'translations' => [
                    'add_item'                => ($this->t)('menu.add_item'),
                    'edit_item'               => ($this->t)('menu.edit_item'),
                    'delete_confirm'          => ($this->t)('menu.delete_confirm'),
                    'label_required'          => ($this->t)('menu.label_required'),
                    'url_required'            => ($this->t)('menu.url_required'),
                    'save_failed'             => ($this->t)('menu.save_failed'),
                    'save_error'              => ($this->t)('menu.save_error'),
                    'delete_failed'           => ($this->t)('menu.delete_failed'),
                    'delete_error'            => ($this->t)('menu.delete_error'),
                    'load_error'              => ($this->t)('menu.load_error'),
                    'error'                   => ($this->t)('menu.error'),
                    'positions_error'         => ($this->t)('menu.positions_error'),
                    'positions_error_generic' => ($this->t)('menu.positions_error_generic'),
                    'modal_title'             => ($this->t)('menu.modal_title'),
                    'field_label'             => ($this->t)('menu.field_label'),
                    'field_url'               => ($this->t)('menu.field_url'),
                    'field_url_placeholder'   => ($this->t)('menu.field_url_placeholder'),
                    'field_group'             => ($this->t)('menu.field_group'),
                    'field_none'              => ($this->t)('menu.field_none'),
                    'field_visible_for'       => ($this->t)('menu.field_visible_for'),
                    'field_members'           => ($this->t)('menu.field_members'),
                    'field_contacts'          => ($this->t)('menu.field_contacts'),
                    'field_anonymous'         => ($this->t)('menu.field_anonymous'),
                    'field_type'              => ($this->t)('menu.field_type'),
                    'type_link'               => ($this->t)('menu.type_link'),
                    'type_heading'            => ($this->t)('menu.type_heading'),
                    'type_divider'            => ($this->t)('menu.type_divider'),
                    'type_submenu'            => ($this->t)('menu.type_submenu'),
                    'field_icon'              => ($this->t)('menu.field_icon'),
                    'field_icon_placeholder'  => ($this->t)('menu.field_icon_placeholder'),
                    'field_parent'            => ($this->t)('menu.field_parent'),
                    'btn_cancel'              => ($this->t)('cancel'),
                    'btn_save'                => ($this->t)('save'),
                    'page_title'              => ($this->t)('menu.page_title'),
                    'tab_navbar'              => ($this->t)('menu.tab_navbar'),
                    'tab_sidebar'             => ($this->t)('menu.tab_sidebar'),
                    'col_name'                => ($this->t)('menu.col_name'),
                    'col_url'                 => ($this->t)('menu.col_url'),
                    'col_group'               => ($this->t)('menu.col_group'),
                    'col_members'             => ($this->t)('menu.col_members'),
                    'col_contacts'            => ($this->t)('menu.col_contacts'),
                    'col_anonymous'           => ($this->t)('menu.col_anonymous'),
                    'col_actions'             => ($this->t)('menu.col_actions'),
                    'col_type'                => ($this->t)('menu.col_type'),
                    'col_icon'                => ($this->t)('menu.col_icon'),
                    'col_label'               => ($this->t)('menu.col_label'),
                    'col_parent'              => ($this->t)('menu.col_parent'),
                ],
            ]));
        }
    }

    public function showArwards()
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if ($person && $this->menuItemDataHelper->authorizedUser('/menuitem/show/arwards', $person)) {
            $arwardsDataHelper = new ArwardsDataHelper($this->application);

            $this->render('Webmaster/views/arwards.latte', $this->getAllParams([
                'counterNames' => $counterNames = $arwardsDataHelper->getCounterNames(),
                'data'         => $arwardsDataHelper->getData($counterNames),
                'groups'       => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'layout'       => $this->getLayout(),
                'navItems'     => $this->getNavItems($person),
                'page'         => $this->application->getConnectedUser()->getPage(),
            ]));
        } else {
            $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
        }
    }

    public function showArticle($id)
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if ($this->menuItemDataHelper->authorizedUser("/menu/show/article/$id", $person)) {
            $this->render('Webmaster/views/navbar/article.latte', $this->getAllParams([
                'navItems'         => $this->getNavItems($person),
                'chosenArticle'    => $this->dataHelper->get('Article', ['Id' => $id], 'Content'),
                'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization(),
                'page'             => $this->application->getConnectedUser()->getPage(),
            ]));
        } else {
            $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
        }
    }
}
