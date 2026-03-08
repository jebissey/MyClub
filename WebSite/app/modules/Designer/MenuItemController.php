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

        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isMenuDesigner())) {
            $this->render('Designer/views/menuItem.latte', $this->getAllParams([
                'navbarItems'  => array_filter($menuItems, fn($i) => $i->What === 'navbar'),
                'sidebarItems' => array_filter($menuItems, fn($i) => $i->What === 'sidebar'),
                'groups'       => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'page'         => $this->application->getConnectedUser()->getPage(),
                'activeTab'    => $tab,
                'translations' => [
                    'add_item'                => $this->languagesDataHelper->translate('menu.add_item'),
                    'edit_item'               => $this->languagesDataHelper->translate('menu.edit_item'),
                    'delete_confirm'          => $this->languagesDataHelper->translate('menu.delete_confirm'),
                    'label_required'          => $this->languagesDataHelper->translate('menu.label_required'),
                    'url_required'            => $this->languagesDataHelper->translate('menu.url_required'),
                    'save_failed'             => $this->languagesDataHelper->translate('menu.save_failed'),
                    'save_error'              => $this->languagesDataHelper->translate('menu.save_error'),
                    'delete_failed'           => $this->languagesDataHelper->translate('menu.delete_failed'),
                    'delete_error'            => $this->languagesDataHelper->translate('menu.delete_error'),
                    'load_error'              => $this->languagesDataHelper->translate('menu.load_error'),
                    'error'                   => $this->languagesDataHelper->translate('menu.error'),
                    'positions_error'         => $this->languagesDataHelper->translate('menu.positions_error'),
                    'positions_error_generic' => $this->languagesDataHelper->translate('menu.positions_error_generic'),
                    'modal_title'             => $this->languagesDataHelper->translate('menu.modal_title'),
                    'field_label'             => $this->languagesDataHelper->translate('menu.field_label'),
                    'field_url'               => $this->languagesDataHelper->translate('menu.field_url'),
                    'field_url_placeholder'   => $this->languagesDataHelper->translate('menu.field_url_placeholder'),
                    'field_group'             => $this->languagesDataHelper->translate('menu.field_group'),
                    'field_none'              => $this->languagesDataHelper->translate('menu.field_none'),
                    'field_visible_for'       => $this->languagesDataHelper->translate('menu.field_visible_for'),
                    'field_members'           => $this->languagesDataHelper->translate('menu.field_members'),
                    'field_contacts'          => $this->languagesDataHelper->translate('menu.field_contacts'),
                    'field_anonymous'         => $this->languagesDataHelper->translate('menu.field_anonymous'),
                    'field_type'              => $this->languagesDataHelper->translate('menu.field_type'),
                    'type_link'               => $this->languagesDataHelper->translate('menu.type_link'),
                    'type_heading'            => $this->languagesDataHelper->translate('menu.type_heading'),
                    'type_divider'            => $this->languagesDataHelper->translate('menu.type_divider'),
                    'type_submenu'            => $this->languagesDataHelper->translate('menu.type_submenu'),
                    'field_icon'              => $this->languagesDataHelper->translate('menu.field_icon'),
                    'field_icon_placeholder'  => $this->languagesDataHelper->translate('menu.field_icon_placeholder'),
                    'field_parent'            => $this->languagesDataHelper->translate('menu.field_parent'),
                    'btn_cancel'              => $this->languagesDataHelper->translate('cancel'),
                    'btn_save'                => $this->languagesDataHelper->translate('save'),
                    'page_title'              => $this->languagesDataHelper->translate('menu.page_title'),
                    'tab_navbar'              => $this->languagesDataHelper->translate('menu.tab_navbar'),
                    'tab_sidebar'             => $this->languagesDataHelper->translate('menu.tab_sidebar'),
                    'col_name'                => $this->languagesDataHelper->translate('menu.col_name'),
                    'col_url'                 => $this->languagesDataHelper->translate('menu.col_url'),
                    'col_group'               => $this->languagesDataHelper->translate('menu.col_group'),
                    'col_members'             => $this->languagesDataHelper->translate('menu.col_members'),
                    'col_contacts'            => $this->languagesDataHelper->translate('menu.col_contacts'),
                    'col_anonymous'           => $this->languagesDataHelper->translate('menu.col_anonymous'),
                    'col_actions'             => $this->languagesDataHelper->translate('menu.col_actions'),
                    'col_type'                => $this->languagesDataHelper->translate('menu.col_type'),
                    'col_icon'                => $this->languagesDataHelper->translate('menu.col_icon'),
                    'col_label'               => $this->languagesDataHelper->translate('menu.col_label'),
                    'col_parent'              => $this->languagesDataHelper->translate('menu.col_parent'),
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
