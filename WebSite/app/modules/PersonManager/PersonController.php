<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use app\enums\FilterInputRule;
use app\enums\PersonStatus;
use app\helpers\Application;
use app\helpers\MyClubDateTime;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\PersonManager\viewModels\MembershipSettingsViewModel;
use app\modules\PersonManager\viewModels\PersonManagerHomeViewModel;
use app\modules\PersonManager\viewModels\PersonsIndexViewModel;
use app\modules\User\viewModels\UserAccountViewModel;

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
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $this->dataHelper->set('Person', ['Inactivated' => 0], ['Id' => $id]);
            $this->redirect('/persons');
        }
    }

    public function create(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $this->redirect('/person/edit/' . $this->personDataHelper->create());
        }
    }

    public function delete(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $this->dataHelper->set('Person', ['Inactivated' => 1], ['Id' => $id]);
            $this->redirect('/persons');
        }
    }

    public function edit(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $row = $this->dataHelper->get('Person', ['Id' => $id], 'Id, Imported, Email, FirstName, LastName, Alert, MemberInfo');
            if (!$row) {
                $this->raiseBadRequest("Unknown person {$id}", __FILE__, __LINE__);
                return;
            }
            /** @var object{Id: int|string, Imported: bool|int|string|null, Email: string, FirstName: string|null, LastName: string|null, Alert: string|null, MemberInfo: string|null} $row */

            $viewModel = new UserAccountViewModel(
                readOnly: (bool)($row->Imported ?? false),
                email: $row->Email,
                firstName: $row->FirstName ?? '',
                lastName: $row->LastName ?? '',
                nickName: '',
                avatar: '',
                useGravatar: '',
                emojis: [],
                isSelfEdit: false,
                i18n: [
                    'account.form.emoji.select_label'     => ($this->t)('account.form.emoji.select_label'),
                    'account.form.emoji.missing_elements' => ($this->t)('account.form.emoji.missing_elements'),
                    'account.form.emoji.none_detected'    => ($this->t)('account.form.emoji.none_detected'),
                    'account.form.emoji.selected'         => ($this->t)('account.form.emoji.selected'),
                ],
                layout: $this->getLayout(),
                alert: $row->Alert ?? '',
                memberInfo: $row->MemberInfo ?? '',
                layoutParams: $this->getAllParams([]),
            );

            $this->render('User/views/user_account.latte', $viewModel->toArray());
        }
    }

    public function editSave(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $row = $this->dataHelper->get('Person', ['Id' => $id], 'Id, Imported, Email, FirstName, LastName');
            if (!$row) {
                $this->raiseBadRequest("Unknown person {$id}", __FILE__, __LINE__);
                return;
            }
            /** @var object{Id: int|string, Imported: bool|int|string|null, Email: string, FirstName: string|null, LastName: string|null} $row */
            $personId = (int)$row->Id;
            $personEmail = $row->Email;
            $personFirstName = $row->FirstName;
            $personLastName = $row->LastName;
            $personImported = (bool)($row->Imported ?? false);

            $schema = [
                'email'      => FilterInputRule::Email->value,
                'firstName'  => FilterInputRule::PersonName->value,
                'lastName'   => FilterInputRule::PersonName->value,
                'alert'      => FilterInputRule::Content->value,
                'memberInfo' => FilterInputRule::Content->value,
            ];
            /** @var array{email?: string, firstName?: string, lastName?: string, alert?: string, memberInfo?: string} $input */
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $email = strtolower(trim($input['email'] ?? ''));
            if (empty($email)) {
                $this->raiseBadRequest("Missing email", __FILE__, __LINE__);
                return;
            }
            $existingRow = $this->dataHelper->get(
                'Person',
                ['Email' => $email],
                'Id, FirstName, LastName, Inactivated'
            );

            $isDuplicate = false;
            $fullName = '';
            $status = '';

            if ($existingRow) {
                /** @var object{Id: int|string, FirstName: string|null, LastName: string|null, Inactivated: bool|int|string|null} $existingRow */
                $existingId = (int)$existingRow->Id;
                $existingInactivated = (bool)($existingRow->Inactivated ?? false);

                $isNewRecord = (
                    $personEmail === '' &&
                    ($personFirstName ?? '') === '' &&
                    ($personLastName ?? '') === '' &&
                    !$personImported
                );
                $isDuplicate = $isNewRecord ? true : ($existingId !== $personId);

                if ($isDuplicate) {
                    $fullName = trim(($existingRow->FirstName ?? '') . ' ' . ($existingRow->LastName ?? ''));
                    $status = $existingInactivated ? 'Disabled' : 'Active';
                }
            }

            if ($isDuplicate) {
                $message = ($this->t)('person.add.emailAlreadyExistsDetailed');
                $message = str_replace(
                    ['{name}', '{status}', '{email}'],
                    [$fullName, $status, $email],
                    $message
                );

                $viewModel = new InfoViewModel(
                    content: $message,
                    timer: 10000,
                    hasAuthorization: $this->application->getConnectedUser()->hasAutorization(),
                    previousPage: true,
                    layoutParams: $this->getAllParams([]),
                );

                $this->render('Common/views/info.latte', $viewModel->toArray());

                return;
            }

            $this->dataHelper->set(
                'Person',
                [
                    'FirstName' => $input['firstName'] ?? '???',
                    'LastName'  => $input['lastName'] ?? '???',
                ],
                ['Id' => $personId]
            );

            // Email is the sync key for imported records — never update it
            if (!$personImported) {
                $this->dataHelper->set('Person', ['Email' => $email], ['Id' => $personId]);
            }

            if ($this->application->getConnectedUser()->isPersonManager()) {
                $this->dataHelper->set(
                    'Person',
                    [
                        'Alert' => $input['alert'] ?? '',
                        'MemberInfo' => $input['memberInfo'] ?? ''
                    ],
                    ['Id' => $personId]
                );
            }

            $this->redirect('/persons');
        }
    }

    public function help(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $lang = TranslationManager::getCurrentLanguage();
            $helpRow = $this->dataHelper->get('Languages', ['Name' => 'Help_PersonManager'], $lang);
            $content = ($helpRow !== false && isset($helpRow->$lang)) ? $helpRow->$lang : '';

            $viewModel = new InfoViewModel(
                content: $content,
                timer: 0,
                hasAuthorization: $this->application->getConnectedUser()->hasAutorization(),
                previousPage: true,
                layoutParams: $this->getAllParams([]),
            );

            $this->render('Common/views/info.latte', $viewModel->toArray());
        }
    }

    public function home(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $_SESSION['navbar'] = 'personManager';

            $viewModel = new PersonManagerHomeViewModel(
                content: ($this->t)('PersonManager'),
                layoutParams: $this->getAllParams([]),
            );

            $this->render('PersonManager/views/personManager.latte', $viewModel->toArray());
        }
    }

    public function index(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
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
        /** @var array<string, string> $filterValues */
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

        /** @var list<string> $statusValues */
        $statusValues = $this->application->enumToValues(PersonStatus::class);

        /** @var string $status */
        $status = WebApp::getFiltered(
            'status',
            $statusValues,
            $this->flight->request()->query->getData()
        ) ?: PersonStatus::Active->value;
        $data = match ($status) {
            PersonStatus::Active->value => $this->prepareTableData(
                $this->tableControllerDataHelper->getActivePersonsQuery(),
                $filterValues
            ),
            PersonStatus::Desactivated->value => $this->prepareTableData(
                $this->tableControllerDataHelper->getDesactivatedPersonsQuery(),
                $filterValues
            ),

            default => Application::unreachable("Unknown status {$status}", __FILE__, __LINE__)
        };

        $viewModel = new PersonsIndexViewModel(
            persons: array_values($data['items']),
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            filters: $filterConfig,
            columns: $columns,
            resetUrl: '/persons',
            status: $status,
            extraParams: $status !== PersonStatus::Active->value ? ['status' => $status] : [],
            layoutParams: $this->getAllParams([]),
        );

        $this->render('PersonManager/views/users_index.latte', $viewModel->toArray());
    }

    public function membershipSettingsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $seasonStart = $this->dataHelper->getSetting('Membership_Season_Start', '');
            $seasonEnd   = $this->dataHelper->getSetting('Membership_Season_End', '');
            $season      = MyClubDateTime::getSeasonRange($seasonStart, $seasonEnd);

            $viewModel = new MembershipSettingsViewModel(
                navItems: $this->getNavItems($this->application->getConnectedUser()->person),
                settings: [
                    'amount'      => (int)$this->dataHelper->getSetting('Membership_Amount', '0') / 100,
                    'seasonStart' => $season['start'],
                    'seasonEnd'   => $season['end'],
                ],
                layoutParams: $this->getAllParams([]),
            );

            $this->render('PersonManager/views/membershipSettings.latte', $viewModel->toArray());
        }
    }

    public function membershipSettingsSave(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $schema = [
                'amount'      => FilterInputRule::Float->value,
                'seasonStart' => FilterInputRule::Text->value,
                'seasonEnd'   => FilterInputRule::Text->value,
            ];

            /** @var array{amount?: float, seasonStart?: string, seasonEnd?: string} $input */
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $amountInCents = (int)round(($input['amount'] ?? 0) * 100);
            $season        = MyClubDateTime::getSeasonRange($input['seasonStart'] ?? '', $input['seasonEnd'] ?? '');

            $this->dataHelper->setSetting('Membership_Amount', (string)$amountInCents);
            $this->dataHelper->setSetting('Membership_Season_Start', $season['start']);
            $this->dataHelper->setSetting('Membership_Season_End', $season['end']);

            $this->redirect('/personManager');
        }
    }
}
