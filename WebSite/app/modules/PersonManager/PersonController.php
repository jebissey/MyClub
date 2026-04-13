<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use app\enums\FilterInputRule;
use app\enums\PersonStatus;
use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;


class PersonController extends TableController
{
    public function __construct(
        Application $application,
        private TableControllerDataHelper $tableControllerDataHelper,
        private PersonDataHelper $personDataHelper,
    ) {
        parent::__construct($application);
    }

    public function activate(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager())) {
            $this->dataHelper->set('Person', ['Inactivated' => 0], ['Id' => $id]);
            $this->redirect('/persons');
        }
    }

    public function create(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager())) {
            $this->redirect('/person/edit/' . $this->personDataHelper->create());
        }
    }

    public function delete(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager())) {
            $this->dataHelper->set('Person', ['Inactivated' => 1], ['Id' => $id]);
            $this->redirect('/persons');
        }
    }

    public function edit(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager())) {
            $person = $this->dataHelper->get('Person', ['Id' => $id], 'Id, Imported, Email, FirstName, LastName, Alert');
            if (!$person) {
                $this->raiseBadRequest("Unknown person {$id}", __FILE__, __LINE__);
                return;
            }
            $this->render('User/views/user_account.latte', $this->getAllParams([
                'readOnly' => $person->Imported == 1 ? true : false,
                'email' => $person->Email,
                'firstName' => $person->FirstName,
                'lastName' => $person->LastName,
                'alert' => $person->Alert ?? '',
                'memberInfo' => $person->MemberInfo ?? '',
                'isSelfEdit' => false,
                'layout' => $this->getLayout(),
                'page' => $this->application->getConnectedUser()->getPage(),
                'translations' => [
                    'account.form.emoji.select_label'     => $this->languagesDataHelper->translate('account.form.emoji.select_label'),
                    'account.form.emoji.missing_elements' => $this->languagesDataHelper->translate('account.form.emoji.missing_elements'),
                    'account.form.emoji.none_detected'    => $this->languagesDataHelper->translate('account.form.emoji.none_detected'),
                    'account.form.emoji.selected'         => $this->languagesDataHelper->translate('account.form.emoji.selected'),
                ],
            ]));
        }
    }

    public function editSave(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isPersonManager())) {
            $person = $this->dataHelper->get('Person', ['Id' => $id], 'Id, Imported, Email, FirstName, LastName');
            if (!$person) {
                $this->raiseBadRequest("Unknown person {$id}", __FILE__, __LINE__);
                return;
            }
            $schema = [
                'email'      => FilterInputRule::Email->value,
                'firstName'  => FilterInputRule::PersonName->value,
                'lastName'   => FilterInputRule::PersonName->value,
                'alert'      => FilterInputRule::Content->value,
                'memberInfo' => FilterInputRule::Content->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $email = strtolower(trim($input['email'] ?? ''));
            if (empty($email)) {
                $this->raiseBadRequest("Missing email", __FILE__, __LINE__);
                return;
            }
            $existing = $this->dataHelper->get(
                'Person',
                ['Email' => $email],
                'Id, FirstName, LastName, Inactivated'
            );
            $isNewRecord = (
                $existing !== false &&
                $person->Email === '' &&
                $person->FirstName === '' &&
                $person->LastName === '' &&
                $person->Imported == 0
            );
            $isDuplicate = $isNewRecord
                ? $existing !== null
                : ($existing && $existing->Id !== $person->Id);

            if ($isDuplicate) {
                $fullName = trim(($existing->FirstName ?? '') . ' ' . ($existing->LastName ?? ''));
                $status = ($existing->Inactivated ?? 1) ? 'Disabled' : 'Active';

                $message = $this->languagesDataHelper->translate('person.add.emailAlreadyExistsDetailed');
                $message = str_replace(
                    ['{name}', '{status}', '{email}'],
                    [$fullName, $status, $email],
                    $message
                );

                $this->render('Common/views/info.latte', [
                    'content' => $message,
                    'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization() ?? false,
                    'currentVersion' => Application::VERSION,
                    'timer' => 10000,
                    'previousPage' => true,
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]);

                return;
            }

            $this->dataHelper->set(
                'Person',
                [
                    'FirstName' => $input['firstName'] ?? '???',
                    'LastName'  => $input['lastName'] ?? '???',
                ],
                ['Id' => $person->Id]
            );

            // Email is the sync key for imported records — never update it
            if ($person->Imported == 0) {
                $this->dataHelper->set('Person', ['Email' => $email], ['Id' => $person->Id]);
            }

            if ($this->application->getConnectedUser()->isPersonManager()) {
                $this->dataHelper->set('Person', ['Alert' => $input['alert']], ['Id' => $person->Id]);
            }

            $this->redirect('/persons');
        }
    }

    public function help(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager())) {
            $lang = TranslationManager::getCurrentLanguage();
            $this->render('Common/views/info.latte', [
                'content' => $this->dataHelper->get('Languages', ['Name' => 'Help_PersonManager'], $lang)->$lang ?? '',
                'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization(),
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true,
                'page' => $this->application->getConnectedUser()->getPage(),
            ]);
        }
    }

    public function home(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager())) {
            $_SESSION['navbar'] = 'personManager';

            $this->render('Webmaster/views/personManager.latte', $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
                'content' => $this->languagesDataHelper->translate('PersonManager')
            ]));
        }
    }

    public function index(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager())) {
            return;
        }

        $schema = [
            'firstName' => FilterInputRule::PersonName->value,
            'lastName' => FilterInputRule::PersonName->value,
            'nickName' => FilterInputRule::PersonName->value,
            'email' => FilterInputRule::Email->value,
            'alert' => FilterInputRule::Text->value,
            'passwordCreated' => ['oui', 'non'],
            'presentInDirectory' => ['oui', 'non'],
            'memberInfo' => FilterInputRule::Text->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());
        $filterConfig = [
            ['name' => 'firstName', 'label' => 'Prénom'],
            ['name' => 'lastName', 'label' => 'Nom'],
            ['name' => 'nickName', 'label' => 'Surnom'],
            ['name' => 'email', 'label' => 'Email'],
            ['name' => 'alert', 'label' => 'Alerte'],
            ['name' => 'passwordCreated', 'label' => 'Mot de passe'],
            ['name' => 'presentInDirectory', 'label' => 'Présentation'],
            ['name' => 'memberInfo', 'label' => 'Informations sur le membre'],
        ];
        $columns = [
            ['field' => 'LastName', 'label' => 'Nom'],
            ['field' => 'FirstName', 'label' => 'Prénom'],
            ['field' => 'Email', 'label' => 'Email'],
            ['field' => 'Phone', 'label' => 'Téléphone'],
            ['field' => 'Alert', 'label' => 'Alerte'],
            ['field' => 'PasswordCreated', 'label' => 'Mot de passe'],
            ['field' => 'PresentInDirectory', 'label' => 'Présentation'],
            ['field' => 'MemberInfo', 'label' => 'Informations sur le membre'],
        ];

        $status = WebApp::getFiltered('status', $this->application->enumToValues(PersonStatus::class), $this->flight->request()->query->getData()) ?: PersonStatus::Active->value;
        $data = match ($status) {
            PersonStatus::Active->value => $this->prepareTableData($this->tableControllerDataHelper->getActivePersonsQuery(), $filterValues),
            PersonStatus::Desactivated->value => $this->prepareTableData($this->tableControllerDataHelper->getDesactivatedPersonsQuery(), $filterValues),

            default => Application::unreachable("Unknown status {$status}", __FILE__, __LINE__)
        };

        $this->render('PersonManager/views/users_index.latte', $this->getAllParams([
            'persons' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/persons',
            'page' => $this->application->getConnectedUser()->getPage(),
            'status' => $status,
            'extraParams' => $status !== PersonStatus::Active->value ? ['status' => $status] : [],
        ]));
    }
}
