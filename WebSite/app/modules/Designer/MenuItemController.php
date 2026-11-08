<?php

declare(strict_types=1);

namespace app\modules\Designer;

use app\enums\ApplicationError;
use app\enums\MenuItemTab;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\To;
use app\models\ArwardsDataHelper;
use app\models\CarouselDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Designer\viewModels\MenuArticleViewModel;
use app\modules\Designer\viewModels\MenuItemViewModel;
use app\modules\Webmaster\viewModels\ArwardsViewModel;

final class MenuItemController extends AbstractController
{
    /**
     * Key given to the JavaScript => translation key.
     *
     * @var array<string, string>
     */
    private const I18N_KEYS = [
        'add_item'                => 'menu.add_item',
        'edit_item'               => 'menu.edit_item',
        'delete_confirm'          => 'menu.delete_confirm',
        'label_required'          => 'menu.label_required',
        'url_required'            => 'menu.url_required',
        'save_failed'             => 'menu.save_failed',
        'save_error'              => 'menu.save_error',
        'delete_failed'           => 'menu.delete_failed',
        'delete_error'            => 'menu.delete_error',
        'load_error'              => 'menu.load_error',
        'error'                   => 'menu.error',
        'positions_error'         => 'menu.positions_error',
        'positions_error_generic' => 'menu.positions_error_generic',
        'modal_title'             => 'menu.modal_title',
        'field_label'             => 'menu.field_label',
        'field_url'               => 'menu.field_url',
        'field_url_placeholder'   => 'menu.field_url_placeholder',
        'field_group'             => 'menu.field_group',
        'field_none'              => 'menu.field_none',
        'field_visible_for'       => 'menu.field_visible_for',
        'field_members'           => 'menu.field_members',
        'field_contacts'          => 'menu.field_contacts',
        'field_anonymous'         => 'menu.field_anonymous',
        'field_type'              => 'menu.field_type',
        'type_link'               => 'menu.type_link',
        'type_heading'            => 'menu.type_heading',
        'type_divider'            => 'menu.type_divider',
        'type_submenu'            => 'menu.type_submenu',
        'field_icon'              => 'menu.field_icon',
        'field_icon_placeholder'  => 'menu.field_icon_placeholder',
        'field_parent'            => 'menu.field_parent',
        'btn_cancel'              => 'cancel',
        'btn_save'                => 'save',
        'page_title'              => 'menu.page_title',
        'tab_navbar'              => 'menu.tab_navbar',
        'tab_sidebar'             => 'menu.tab_sidebar',
        'col_name'                => 'menu.col_name',
        'col_url'                 => 'menu.col_url',
        'col_group'               => 'menu.col_group',
        'col_members'             => 'menu.col_members',
        'col_contacts'            => 'menu.col_contacts',
        'col_anonymous'           => 'menu.col_anonymous',
        'col_actions'             => 'menu.col_actions',
        'col_type'                => 'menu.col_type',
        'col_icon'                => 'menu.col_icon',
        'col_label'               => 'menu.col_label',
        'col_parent'              => 'menu.col_parent',
    ];

    public function __construct(Application $application, private readonly CarouselDataHelper $carouselDataHelper)
    {
        parent::__construct($application);
    }

    public function index(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        $menuItems = $this->getNavItems($connectedUser->person?->email, true);
        $tab = MenuItemTab::tryFrom(To::str($this->flight->request()->query->tab ?? '')) ?? MenuItemTab::Navbar;

        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isMenuDesigner(), __FILE__, __LINE__)) {
            $viewModel = new MenuItemViewModel(
                navbarItems: array_values(array_filter($menuItems, static fn($i) => $i->what === 'navbar')),
                sidebarItems: array_values(array_filter($menuItems, static fn($i) => $i->what === 'sidebar')),
                groups: $this->getActiveGroups(),
                activeTab: $tab,
                i18n: array_map(fn(string $key): string => $this->translate($key), self::I18N_KEYS),
                layoutParams: $this->getAllParams(['page' => $connectedUser->getPage()]),
            );

            $this->views->render('Designer/views/menuItem.latte', $viewModel);
        }
    }

    public function showArwards(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        $person = $connectedUser->person;
        if ($person !== null && $this->menuItemDataHelper->authorizedUser('/menuitem/show/arwards', $person)) {
            $arwardsDataHelper = new ArwardsDataHelper($this->application);
            $counterNames = $arwardsDataHelper->getCounterNames();

            $viewModel = new ArwardsViewModel(
                counterNames: $counterNames,
                data: $arwardsDataHelper->getData($counterNames),
                groups: $this->getActiveGroups(),
                layout: $this->getLayout(),
                navItems: $this->getNavItems($person->email),
                layoutParams: $this->getAllParams(['page' => $connectedUser->getPage()]),
            );

            $this->views->render('Webmaster/views/arwards.latte', $viewModel);
        } else {
            $this->application->getErrorManager()->raise(
                ApplicationError::Forbidden,
                'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__
            );
        }
    }

    public function showArticle(int $id): void
    {
        $connectedUser = $this->application->getConnectedUser();
        $person = $connectedUser->person;

        if (!$this->menuItemDataHelper->authorizedUser("/menu/show/article/$id", $person)) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Forbidden,
                'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__
            );
            return;
        }

        try {
            $viewModel = new MenuArticleViewModel(
                chosenArticle: $this->dataHelper->get('Article', ['Id' => $id], 'Content'),
                carouselItems: $this->carouselDataHelper->getCarouselItems($id),
                navItems: $this->getNavItems($person?->email),
                hasAuthorization: $connectedUser->hasAutorization(),
                layoutParams: $this->getAllParams([]),
            );

            $this->views->render('Article/views/showArticle.latte', $viewModel);
        } catch (QueryException $e) {
            $this->errors->badRequest($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }
}
