<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\models\MemberDataHelper;
use app\models\MemberDirectoryDataHelper;
use app\models\PersonGroupDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\MemberMapService;
use app\modules\User\viewModels\UserDirectoryViewModel;
use app\modules\User\viewModels\UsersMapViewModel;
use app\modules\Common\valueObjects\LocatedMemberRow;

final class UserDirectoryController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly GroupDataHelper $groupDataHelper,
        private readonly PersonGroupDataHelper $personGroupDataHelper,
        private readonly MemberDataHelper $memberDataHelper,
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

        $filterMyGroups   = $this->getQueryBool('my') ?? false;
        $filterSelfEnroll = $this->getQueryBool('self') ?? false;
        $filterWithAuth   = $this->getQueryBool('auth') ?? false;
        $selectedGroup    = $this->getQueryInt('group');

        $person = $this->application->getConnectedUser(true)->person;

        // Persons list
        if ($selectedGroup !== null) {
            $persons = $this->memberDirectoryDataHelper->getPersonsInGroupForDirectory($selectedGroup);
        } else {
            $gravatarHandler = new GravatarHandler();
            $persons = array_map(
                static fn(LocatedMemberRow $member): LocatedMemberRow => $member->withUserImg(
                    WebApp::computeUserImg(
                        (bool) $member->UseGravatar,
                        $member->Email,
                        $member->Avatar,
                        $gravatarHandler,
                    ),
                ),
                $this->memberDataHelper->getActiveMembersDirectoryInfo(),
            );
        }

        // Logged person presentation status
        $loggedPersonRow = $this->dataHelper->get(
            'Member',
            ['Id' => $person->Id],
            'InPresentationDirectory'
        );
        /** @var object{InPresentationDirectory: bool|int|string|null}|false $loggedPersonRow */
        $loggedPersonInPresentationDirectory = $loggedPersonRow !== false
            ? (bool) ($loggedPersonRow->InPresentationDirectory ?? false)
            : false;

        // Filtered groups
        $groups = $this->groupDataHelper->getGroupsForDirectory(
            personId: $person->Id,
            onlyMyGroups: $filterMyGroups,
            onlySelfEnrollable: $filterSelfEnroll,
            onlyWithAuthorization: $filterWithAuth
        );

        // Preserve current filters in all generated URLs
        $queryFilters = array_filter([
            'my'   => $filterMyGroups   ? '1' : null,
            'self' => $filterSelfEnroll ? '1' : null,
            'auth' => $filterWithAuth   ? '1' : null,
        ]);

        $allGroupsUrl = '/user/directory' . ($queryFilters ? '?' . http_build_query($queryFilters) : '');

        foreach ($groups as $group) {
            $params = $queryFilters + ['group' => $group->Id];
            $group->url = '/user/directory?' . http_build_query($params);
        }

        $viewModel = new UserDirectoryViewModel(
            persons: $persons,
            navItems: $this->getNavItems($person),
            loggedPersonInPresentationDirectory: $loggedPersonInPresentationDirectory,
            groups: $groups,
            groupCounts: $this->groupDataHelper->getGroupCount(),
            selectedGroup: $selectedGroup,
            countOfMessages: count($this->dataHelper->gets('Message', [
                '"From"' => 'User',
                'GroupId' => $selectedGroup
            ], '*')),
            userIsInGroup: $this->personGroupDataHelper->isPersonInGroup($person->Id, $selectedGroup ?? 0),
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
            navItems: $this->getNavItems($person),
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
            navItems: $this->getNavItems($this->application->getConnectedUser(true)->person),
            title: 'Carte des membres publics',
            isPublic: true,
            maxZoom: 11,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/users_map.latte', $viewModel->toArray());
    }
}
