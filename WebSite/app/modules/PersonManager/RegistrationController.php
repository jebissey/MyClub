<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use Throwable;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;

class RegistrationController extends TableController
{
    public function __construct(
        Application $application,
        private TableControllerDataHelper $tableControllerDataHelper,
        private GroupDataHelper $groupDataHelper,
    ) {
        parent::__construct($application);
    }

    public function index()
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isGroupManager())) {
            $schema = [
                'lastName' => FilterInputRule::PersonName->value,
                'firstName' => FilterInputRule::PersonName->value,
                'nickName' => FilterInputRule::PersonName->value,
            ];
            $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
            $filterConfig = [
                ['name' => 'lastName', 'label' => 'Nom'],
                ['name' => 'firstName', 'label' => 'Prénom'],
                ['name' => 'nickName', 'label' => 'Surnom']
            ];
            $columns = [
                ['field' => 'LastName', 'label' => 'Nom'],
                ['field' => 'FirstName', 'label' => 'Prénom'],
                ['field' => 'NickName', 'label' => 'Surnom']
            ];
            $data = $this->prepareTableData($this->tableControllerDataHelper->getPersonsQuery(), $filterValues);
            $this->render('PersonManager/views/registration_groups_index.latte', $this->getAllParams([
                'persons' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/registration',
                'layout' => $this->getLayout(),
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }

    public function getPersonGroups($personId)
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isGroupManager())) {
            [$availableGroups, $currentGroups] = $this->groupDataHelper->getAvailableGroups($this->application->getConnectedUser(), $personId);

            try {
                $this->render('PersonManager/views/registration_user_groups_partial.latte', $this->getAllParams([
                    'currentGroups' => $currentGroups,
                    'availableGroups' => $availableGroups,
                    'personId' => $personId,
                    'page' => $this->application->getConnectedUser()->getPage()
                ]));
            } catch (Throwable $e) {
                http_response_code(500);
                echo "<div class='alert alert-danger'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}
