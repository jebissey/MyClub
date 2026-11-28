<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use Throwable;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;
use app\modules\PersonManager\viewModels\RegistrationIndexViewModel;
use app\modules\PersonManager\viewModels\RegistrationUserGroupsViewModel;

final class RegistrationController extends TableController
{
    public function __construct(
        Application $application,
        private readonly TableControllerDataHelper $tableControllerDataHelper,
        private readonly GroupDataHelper $groupDataHelper,
    ) {
        parent::__construct($application);
    }

    public function index(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isGroupManager(), __FILE__, __LINE__)) {
            $schema = [
                'lastName' => FilterInputRule::PersonName->value,
                'firstName' => FilterInputRule::PersonName->value,
                'nickName' => FilterInputRule::PersonName->value,
                'email' => FilterInputRule::Email->value,
            ];
            $filterValues = array_map(
                static fn(mixed $value): string => To::str($value),
                WebApp::filterInput($schema, $this->flight->request()->query->getData())
            );
            $filterConfig = [
                ['name' => 'lastName', 'label' => $this->translate('person_manager.registration.column.last_name')],
                ['name' => 'firstName', 'label' => $this->translate('person_manager.registration.column.first_name')],
                ['name' => 'nickName', 'label' => $this->translate('person_manager.registration.column.nickname')],
                ['name' => 'email', 'label' => $this->translate('account.form.email.label')],
            ];
            $columns = [
                ['field' => 'LastName', 'label' => $this->translate('person_manager.registration.column.last_name')],
                ['field' => 'FirstName', 'label' => $this->translate('person_manager.registration.column.first_name')],
                ['field' => 'NickName', 'label' => $this->translate('person_manager.registration.column.nickname')],
                ['field' => 'Email', 'label' => $this->translate('account.form.email.label')],
            ];
            $data = $this->prepareTableData($this->tableControllerDataHelper->getActivePersonsQuery(), $filterValues);

            $viewModel = new RegistrationIndexViewModel(
                persons: $data['items'],
                currentPage: $data['currentPage'],
                totalPages: $data['totalPages'],
                filterValues: $filterValues,
                filters: $filterConfig,
                columns: $columns,
                resetUrl: '/registration',
                layout: $this->resolveLayout(),
                navItems: $this->getNavItems($this->application->getConnectedUser()->person?->email),
                i18n: [
                    'errorLoadGroups' => ($this->t)('person_manager.registration.error.load_groups'),
                    'errorGeneric'    => ($this->t)('person_manager.registration.error.generic'),
                ],
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );

            $this->views->render('PersonManager/views/registration_groups_index.latte', $viewModel);
        }
    }

    public function getPersonGroups(int $personId): void
    {
        $personId = (int)$personId;

        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isGroupManager(), __FILE__, __LINE__)) {
            [$availableGroups, $currentGroups] = $this->groupDataHelper->getAvailableGroups(
                $this->application->getConnectedUser(),
                $personId
            );

            try {
                $viewModel = new RegistrationUserGroupsViewModel(
                    currentGroups: $currentGroups,
                    availableGroups: $availableGroups,
                    personId: $personId,
                    layoutParams: $this->getAllParams([]),
                );

                $this->views->render('PersonManager/views/registration_user_groups_partial.latte', $viewModel);
            } catch (Throwable $e) {
                http_response_code(500);
                echo "<div class='alert alert-danger'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }

    /**
     * The page is reachable from the webmaster menu and from the person manager menu.
     * When the session remembers the menu the user came from, it is kept; on a direct access
     * (no menu in the session) the menu is inferred from the user's rights.
     */
    private function resolveLayout(): string
    {
        $navbar = $_SESSION['navbar'] ?? '';
        if (is_string($navbar) && $navbar !== '') {
            return $this->getLayout();
        }

        return $this->getLayout(
            $this->application->getConnectedUser()->isPersonManager() ? 'personManager' : 'webmaster'
        );
    }
}
