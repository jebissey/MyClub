<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\models\GroupDataHelper;
use app\models\MemberDirectoryDataHelper;
use app\models\PersonGroupDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\MemberMapService;
use app\modules\Common\valueObjects\DirectoryGroupRow;
use app\modules\User\viewModels\UserDirectoryViewModel;
use app\modules\User\viewModels\UsersMapViewModel;

final class UserDirectoryController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly GroupDataHelper $groupDataHelper,
        private readonly PersonGroupDataHelper $personGroupDataHelper,
        private readonly MemberMapService $memberMapService,
        private readonly MemberDirectoryDataHelper $memberDirectoryDataHelper
    ) {
        parent::__construct($application);
    }


    public function showDirectory(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $filterMyGroups = $this->getQueryBool('my') ?? false;
        $filterWithAuth = $this->getQueryBool('auth') ?? false;
        // "Self-enrollable" and "with authorization" are mutually exclusive; "auth" wins
        $filterSelfEnroll = $filterWithAuth ? false : ($this->getQueryBool('self') ?? false);
        $selectedGroup = $this->getQueryInt('group');

        $person = $this->application->getConnectedUser(true)->person;

        $persons = $selectedGroup !== null
            ? $this->memberDirectoryDataHelper->getPersonsInGroupForDirectory($selectedGroup)
            : $this->memberDirectoryDataHelper->getPersonsForDirectory();

        $loggedPersonRow = $this->dataHelper->get(
            'Member',
            ['Id' => $person->id],
            'InPresentationDirectory'
        );
        /** @var object{InPresentationDirectory: bool|int|string|null}|false $loggedPersonRow */
        $loggedPersonInPresentationDirectory = $loggedPersonRow !== false
            && (bool) ($loggedPersonRow->InPresentationDirectory ?? false);

        // Preserve the active filters in every generated URL
        $queryFilters = array_filter([
            'my'   => $filterMyGroups   ? '1' : null,
            'self' => $filterSelfEnroll ? '1' : null,
            'auth' => $filterWithAuth   ? '1' : null,
        ]);

        $allGroupsUrl = '/user/directory' . ($queryFilters ? '?' . http_build_query($queryFilters) : '');

        $groups = array_map(
            static fn(DirectoryGroupRow $group): DirectoryGroupRow => $group->withUrl(
                '/user/directory?' . http_build_query($queryFilters + ['group' => $group->id])
            ),
            $this->groupDataHelper->getGroupsForDirectory(
                personId: $person->id,
                onlyMyGroups: $filterMyGroups,
                onlySelfEnrollable: $filterSelfEnroll,
                onlyWithAuthorization: $filterWithAuth
            )
        );

        $viewModel = new UserDirectoryViewModel(
            persons: $persons,
            navItems: $this->getNavItems($person->email),
            loggedPersonInPresentationDirectory: $loggedPersonInPresentationDirectory,
            groups: $groups,
            groupCounts: $this->groupDataHelper->getGroupCount(),
            selectedGroup: $selectedGroup,
            countOfMessages: count($this->dataHelper->gets('Message', [
                '"From"' => 'User',
                'GroupId' => $selectedGroup
            ], '*')),
            userIsInGroup: $this->personGroupDataHelper->isPersonInGroup($person->id, $selectedGroup ?? 0),
            countOfLocatedMembers: count($persons),
            numberOfPublicMembers: count($this->dataHelper->gets('Member', [
                'InPresentationDirectory' => 1,
                'Inactivated' => 0,
                'MyPublicDataInPresentationDirectory IS NOT NULL AND MyPublicDataInPresentationDirectory != ""' => null
            ], 'Id')),
            totalWithPresentation: count($this->dataHelper->gets('Member', [
                'InPresentationDirectory' => 1,
                'Inactivated' => 0
            ], 'Id')),
            totalPersons: count($this->dataHelper->gets('Member', [
                'Inactivated' => 0
            ], 'Id')),
            filterMyGroups: $filterMyGroups,
            filterSelfEnroll: $filterSelfEnroll,
            filterWithAuth: $filterWithAuth,
            allGroupsUrl: $allGroupsUrl,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/users_directory.latte', $viewModel->toArray());
    }

    public function showMap(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $rows = $this->memberDirectoryDataHelper->getLocatedMembers();
        $locationData = $this->memberMapService->getLocationData($rows);
        $person = $this->application->getConnectedUser()->person;

        $viewModel = new UsersMapViewModel(
            locationData: $locationData,
            membersCount: count($locationData),
            navItems: $this->getNavItems($person?->email),
            title: 'Carte des membres',
            isPublic: false,
            maxZoom: 12,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/users_map.latte', $viewModel->toArray());
    }

    public function showPublicMap(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $rows = $this->memberDirectoryDataHelper->getLocatedMembers(null, true);
        $locationData = $this->memberMapService->getLocationData($rows);

        $viewModel = new UsersMapViewModel(
            locationData: $locationData,
            membersCount: count($locationData),
            navItems: $this->getNavItems($this->application->getConnectedUser(true)->person->email),
            title: 'Carte des membres publics',
            isPublic: true,
            maxZoom: 11,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/users_map.latte', $viewModel->toArray());
    }
}
