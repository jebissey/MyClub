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

class RegistrationController extends TableController
{
    public function __construct(
        Application $application,
        private TableControllerDataHelper $tableControllerDataHelper,
        private GroupDataHelper $groupDataHelper,
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
                ['name' => 'lastName', 'label' => 'Nom'],
                ['name' => 'firstName', 'label' => 'Prénom'],
                ['name' => 'nickName', 'label' => 'Surnom'],
                ['name' => 'email', 'label' => 'Email'],
            ];
            $columns = [
                ['field' => 'LastName', 'label' => 'Nom'],
                ['field' => 'FirstName', 'label' => 'Prénom'],
                ['field' => 'NickName', 'label' => 'Surnom'],
                ['field' => 'Email', 'label' => 'Email'],
            ];
            $data = $this->prepareTableData($this->tableControllerDataHelper->getActivePersonsQuery(), $filterValues);

            $viewModel = new RegistrationIndexViewModel(
                persons: array_values($data['items']),
                currentPage: $data['currentPage'],
                totalPages: $data['totalPages'],
                filterValues: $filterValues,
                filters: $filterConfig,
                columns: $columns,
                resetUrl: '/registration',
                layout: $this->getLayout(),
                navItems: $this->getNavItems($this->application->getConnectedUser()->person),
                i18n: [
                    'errorLoadGroups' => ($this->t)('person_manager.registration.error.load_groups'),
                    'errorGeneric'    => ($this->t)('person_manager.registration.error.generic'),
                ],
                layoutParams: $this->getAllParams([]),
            );

            $this->render('PersonManager/views/registration_groups_index.latte', $viewModel->toArray());
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

                $this->render('PersonManager/views/registration_user_groups_partial.latte', $viewModel->toArray());
            } catch (Throwable $e) {
                http_response_code(500);
                echo "<div class='alert alert-danger'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}
