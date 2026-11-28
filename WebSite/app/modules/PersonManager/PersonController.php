<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use app\enums\FilterInputRule;
use app\enums\PersonStatus;
use app\helpers\Application;
use app\helpers\MemberCustomFields;
use app\helpers\MyClubDateTime;
use app\helpers\ReservedIds;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\PersonManager\viewModels\MembershipSettingsViewModel;
use app\modules\PersonManager\viewModels\PersonManagerHomeViewModel;
use app\modules\PersonManager\viewModels\PersonsIndexViewModel;
use app\modules\User\viewModels\UserAccountViewModel;

final class PersonController extends TableController
{
    public function __construct(
        Application $application,
        private readonly TableControllerDataHelper $tableControllerDataHelper,
        private readonly PersonDataHelper $personDataHelper,
    ) {
        parent::__construct($application);
    }

    public function activate(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $this->dataHelper->set('Member', ['Inactivated' => 0], ['Id' => $id]);
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
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            return;
        }
        if ($id === ReservedIds::WEBMASTER_PERSON) {
            $this->errors->badRequest("Person ({$id}) can't be inactivated", __FILE__, __LINE__);
            return;
        }

        $this->dataHelper->set('Member', ['Inactivated' => 1], ['Id' => $id]);
        $this->redirect('/persons');
    }

    public function edit(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $individual = $this->dataHelper->get(
                'Individual',
                ['Id' => $id],
                'Id, Email, FirstName, LastName'
            );
            $member = $this->dataHelper->get(
                'Member',
                ['Id' => $id],
                'Imported, Alert, MemberInfo, CustomFields'
            );
            if ($individual === false || $member === false) {
                $this->errors->badRequest("Unknown person {$id}", __FILE__, __LINE__);
                return;
            }
            /** @var object{Id: int|string, Email: string, FirstName: string|null, LastName: string|null} $individual */
            /** @var object{Imported: bool|int|string|null, Alert: string|null, MemberInfo: string|null, CustomFields: string|null} $member */

            $definitions = MemberCustomFields::parseDefinitions(
                $this->dataHelper->getSetting(MemberCustomFields::SETTING_KEY, '[]')
            );

            $viewModel = new UserAccountViewModel(
                navItems: $this->getNavItems($this->application->getConnectedUser()->person?->email),
                readOnly: (bool)($member->Imported ?? false),
                email: $individual->Email,
                firstName: $individual->FirstName ?? '',
                lastName: $individual->LastName ?? '',
                nickName: '',
                avatar: '',
                useGravatar: '',
                emojis: [],
                isSelfEdit: false,
                layout: $this->getLayout('personManager'),
                alert: $member->Alert ?? '',
                memberInfo: $member->MemberInfo ?? '',
                customFields: MemberCustomFields::toFormFields(
                    $definitions,
                    MemberCustomFields::decodeValues($member->CustomFields)
                ),
                layoutParams: $this->getAllParams([]),
            );

            $this->views->render('User/views/user_account.latte', $viewModel);
        }
    }

    public function editSave(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $individual = $this->dataHelper->get(
                'Individual',
                ['Id' => $id],
                'Id, Email, FirstName, LastName'
            );
            $member = $this->dataHelper->get('Member', ['Id' => $id], 'Imported, CustomFields');
            if ($individual === false || $member === false) {
                $this->errors->badRequest("Unknown person {$id}", __FILE__, __LINE__);
                return;
            }
            /** @var object{Id: int|string, Email: string, FirstName: string|null, LastName: string|null} $individual */
            /** @var object{Imported: bool|int|string|null, CustomFields: string|null} $member */
            $personId = (int)$individual->Id;
            $personEmail = $individual->Email;
            $personFirstName = $individual->FirstName;
            $personLastName = $individual->LastName;
            $personImported = (bool)($member->Imported ?? false);

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
                $this->errors->badRequest("Missing email", __FILE__, __LINE__);
                return;
            }
            $existingIndividual = $this->dataHelper->get(
                'Individual',
                ['Email' => $email],
                'Id, FirstName, LastName'
            );

            $isDuplicate = false;
            $fullName = '';
            $status = '';

            if ($existingIndividual !== false) {
                /** @var object{Id: int|string, FirstName: string|null, LastName: string|null} $existingIndividual */
                $existingId = (int)$existingIndividual->Id;
                $existingMember = $this->dataHelper->get('Member', ['Id' => $existingId], 'Inactivated');
                $existingInactivated = $existingMember !== false && (bool)($existingMember->Inactivated ?? false);

                $isNewRecord = (
                    $personEmail === '' &&
                    ($personFirstName ?? '') === '' &&
                    ($personLastName ?? '') === '' &&
                    !$personImported
                );
                $isDuplicate = $isNewRecord ? true : ($existingId !== $personId);

                if ($isDuplicate) {
                    $fullName = trim(($existingIndividual->FirstName ?? '') . ' ' . ($existingIndividual->LastName ?? ''));
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
                    previousPage: true,
                    layoutParams: $this->getAllParams([]),
                );

                $this->views->render('Common/views/info.latte', $viewModel);

                return;
            }

            $this->dataHelper->set(
                'Individual',
                [
                    'FirstName' => $input['firstName'] ?? '???',
                    'LastName'  => $input['lastName'] ?? '???',
                ],
                ['Id' => $personId]
            );

            // Email is the sync key for imported records — never update it
            if (!$personImported) {
                $this->dataHelper->set('Individual', ['Email' => $email], ['Id' => $personId]);
            }

            if ($this->application->getConnectedUser()->isPersonManager()) {
                $postData = $this->flight->request()->data->getData();
                $rawCustom = is_array($postData['custom'] ?? null) ? $postData['custom'] : [];
                $definitions = MemberCustomFields::parseDefinitions(
                    $this->dataHelper->getSetting(MemberCustomFields::SETTING_KEY, '[]')
                );
                $customValues = MemberCustomFields::mergeValues(
                    $definitions,
                    MemberCustomFields::decodeValues($member->CustomFields),
                    $rawCustom
                );

                $this->dataHelper->set(
                    'Member',
                    [
                        'Alert' => $input['alert'] ?? '',
                        'MemberInfo' => $input['memberInfo'] ?? '',
                        'CustomFields' => MemberCustomFields::encodeValues($customValues),
                    ],
                    ['Id' => $personId]
                );
            }

            $this->redirect('/persons');
        }
    }

    public function editHelp(): void
    {
        $this->renderHelp('Help_PersonEdit', fn($u) => $u->isPersonManager(), __FILE__, __LINE__);
    }

    public function help(): void
    {
        $this->renderHelp('Help_PersonManager', fn($u) => $u->isPersonManager(), __FILE__, __LINE__);
    }

    public function home(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $this->setNavbar('personManager');

            $viewModel = new PersonManagerHomeViewModel(
                content: '',
                layoutParams: $this->getAllParams([]),
            );

            $this->views->render('PersonManager/views/personManager.latte', $viewModel);
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
        $filterValues = $this->readFilterValues($schema);
        $filterConfig = [
            ['name' => 'firstName', 'label' => $this->translate('person_manager.table.first_name')],
            ['name' => 'lastName', 'label' => $this->translate('person_manager.table.last_name')],
            ['name' => 'nickName', 'label' => $this->translate('person_manager.table.nick_name')],
            ['name' => 'email', 'label' => $this->translate('person_manager.table.email')],
            ['name' => 'alert', 'label' => $this->translate('person_manager.table.alert')],
            $this->yesNoFilter('passwordCreated', 'person_manager.table.password'),
            $this->yesNoFilter('presentInDirectory', 'person_manager.table.presentation'),
            ['name' => 'memberInfo', 'label' => $this->translate('person_manager.table.member_info')],
        ];
        $columns = [
            ['field' => 'LastName', 'label' => $this->translate('person_manager.table.last_name')],
            ['field' => 'FirstName', 'label' => $this->translate('person_manager.table.first_name')],
            ['field' => 'Email', 'label' => $this->translate('person_manager.table.email')],
            ['field' => 'Phone', 'label' => $this->translate('person_manager.table.phone')],
            ['field' => 'Alert', 'label' => $this->translate('person_manager.table.alert')],
            ['field' => 'PasswordCreated', 'label' => $this->translate('person_manager.table.password')],
            ['field' => 'PresentInDirectory', 'label' => $this->translate('person_manager.table.presentation')],
            ['field' => 'MemberInfo', 'label' => $this->translate('person_manager.table.member_info')],
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

            default => Application::unreachable("Unknown status {$status}")
        };

        $viewModel = new PersonsIndexViewModel(
            persons: $data['items'],
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            filters: $filterConfig,
            columns: $columns,
            resetUrl: '/persons',
            status: $status,
            extraParams: $status !== PersonStatus::Active->value ? ['status' => $status] : [],
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );

        $this->views->render('PersonManager/views/users_index.latte', $viewModel);
    }

    public function membershipSettingsEdit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isPersonManager(), __FILE__, __LINE__)) {
            $seasonStart = $this->dataHelper->getSetting('Membership_Season_Start', '');
            $seasonEnd   = $this->dataHelper->getSetting('Membership_Season_End', '');
            $season      = MyClubDateTime::getSeasonRange($seasonStart, $seasonEnd);

            $viewModel = new MembershipSettingsViewModel(
                navItems: $this->getNavItems($this->application->getConnectedUser()->person?->email),
                settings: [
                    'amount'      => (int)$this->dataHelper->getSetting('Membership_Amount', '0') / 100,
                    'seasonStart' => $season['start'],
                    'seasonEnd'   => $season['end'],
                ],
                layoutParams: $this->getAllParams([]),
            );

            $this->views->render('PersonManager/views/membershipSettings.latte', $viewModel);
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
