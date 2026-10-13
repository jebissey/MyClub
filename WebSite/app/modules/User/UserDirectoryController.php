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
use app\modules\Common\valueObjects\Person;

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
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $groupParam = $this->flight->request()->query['group'] ?? null;
        $selectedGroup = null;
        if (is_string($groupParam) && ctype_digit($groupParam)) {
            $selectedGroup = (int) $groupParam;
        }

        if ($selectedGroup) {
            $persons = $this->memberDirectoryDataHelper->getPersonsInGroupForDirectory($selectedGroup);
        } else {
            $persons = $this->memberDataHelper->getActiveMembersDirectoryInfo();

            $gravatarHandler = new GravatarHandler();
            foreach ($persons as $directoryPerson) {
                $directoryPerson->UserImg = WebApp::getUserImg(Person::fromRow($directoryPerson), $gravatarHandler);
            }
        }

        $loggedPersonRow = $this->dataHelper->get(
            'Member',
            ['Id' => $person->Id],
            'InPresentationDirectory'
        );
        /** @var object{InPresentationDirectory: bool|int|string|null}|false $loggedPersonRow */
        $loggedPersonInPresentationDirectory = $loggedPersonRow !== false
            ? (bool)($loggedPersonRow->InPresentationDirectory ?? false)
            : false;

        $viewModel = new UserDirectoryViewModel(
            persons: $persons,
            navItems: $this->getNavItems($person),
            loggedPersonInPresentationDirectory: $loggedPersonInPresentationDirectory,
            groups: array_values($this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name')),
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
            layoutParams: $this->getAllParams([]),
        );

        $this->render('User/views/users_directory.latte', $viewModel->toArray());
    }

    public function showMap(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $rows = $this->memberDirectoryDataHelper->getLocatedMembers();
        $locationData = $this->memberMapService->getLocationData($rows);

        $viewModel = new UsersMapViewModel(
            locationData: $locationData,
            membersCount: count($locationData),
            navItems: $this->getNavItems($person),
            title: 'Carte des membres',
            isPublic: false,
            maxZoom: 12,
            layoutParams: $this->getAllParams([]),
        );

        $this->render('User/views/users_map.latte', $viewModel->toArray());
    }

    public function showPublicMap(): void
    {
        $person = $this->application->getConnectedUser()->person;

        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $rows = $this->memberDirectoryDataHelper->getLocatedMembers(null, true);
        $locationData = $this->memberMapService->getLocationData($rows);

        $viewModel = new UsersMapViewModel(
            locationData: $locationData,
            membersCount: count($locationData),
            navItems: $this->getNavItems($person),
            title: 'Carte des membres publics',
            isPublic: true,
            maxZoom: 11,
            layoutParams: $this->getAllParams([]),
        );

        $this->render('User/views/users_map.latte', $viewModel->toArray());
    }
}
