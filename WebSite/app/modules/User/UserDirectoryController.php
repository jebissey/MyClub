<?php

declare(strict_types=1);

namespace app\modules\User;

use stdClass;
use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\models\PersonDataHelper;
use app\models\PersonGroupDataHelper;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserDirectoryViewModel;
use app\modules\User\viewModels\UsersMapViewModel;
use app\valueObjects\Person;

/**
 * @phpstan-import-type PersonRow from Person
 * @phpstan-type DirectoryMapRow object{
 *     Id: int|string,
 *     FirstName: string|null,
 *     LastName: string|null,
 *     NickName: string|null,
 *     Avatar: string|null,
 *     UseGravatar: bool|int|string|null,
 *     Email: string,
 *     Location: string|null,
 *     MyPublicDataInPresentationDirectory: string|null,
 *     InPresentationDirectory: int|string,
 *     ShowPhoneInPresentationDirectory: int|string,
 *     ShowEmailInPresentationDirectory: int|string
 * }
 */
class UserDirectoryController extends AbstractController
{
    public function __construct(
        Application $application,
        private PersonDataHelper $personDataHelper,
        private GroupDataHelper $groupDataHelper,
        private PersonGroupDataHelper $personGroupDataHelper,
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
            $persons = $this->personDataHelper->getPersonsInGroupForDirectory($selectedGroup);
        } else {
            $persons = $this->dataHelper->gets(
                'Person',
                [
                    'InPresentationDirectory' => 1,
                    'Inactivated' => 0
                ],
                'Id, LastName, FirstName, NickName, UseGravatar, Avatar, Email, '
                    . 'InPresentationDirectory, ShowPhoneInPresentationDirectory, ShowEmailInPresentationDirectory, Location',
                'FirstName, LastName'
            );
            $gravatarHandler = new GravatarHandler();
            foreach ($persons as $person_) {
                /** @var PersonRow $personRow */
                $personRow = $person_;
                $person_->UserImg = WebApp::getUserImg(Person::fromRow($personRow), $gravatarHandler);
            }
        }

        $loggedPersonRow = $this->dataHelper->get('Person', ['Id' => $person->Id], 'InPresentationDirectory');
        /** @var object{InPresentationDirectory: bool|int|string|null}|false $loggedPersonRow */
        $loggedPersonInPresentationDirectory = $loggedPersonRow !== false
            ? (bool)($loggedPersonRow->InPresentationDirectory ?? false)
            : false;

        $viewModel = new UserDirectoryViewModel(
            persons: array_values($persons),
            navItems: $this->getNavItems($person),
            loggedPersonInPresentationDirectory: $loggedPersonInPresentationDirectory,
            groups: array_values($this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name')),
            groupCounts: $this->groupDataHelper->getGroupCount(),
            selectedGroup: $selectedGroup,
            countOfMessages: count($this->dataHelper->gets('Message', [
                '"From"' => 'User',
                'GroupId' => $selectedGroup
            ])),
            userIsInGroup: $this->personGroupDataHelper->isPersonInGroup($person->Id, $selectedGroup ?? 0),
            countOfLocatedMembers: count($persons),
            numberOfPublicMembers: count($this->dataHelper->gets('Person', [
                'InPresentationDirectory' => 1,
                'Inactivated' => 0,
                'MyPublicDataInPresentationDirectory IS NOT NULL AND MyPublicDataInPresentationDirectory != ""' => null
            ], 'Id')),
            totalWithPresentation: count($this->dataHelper->gets('Person', [
                'InPresentationDirectory' => 1,
                'Inactivated' => 0
            ], 'Id')),
            totalPersons: count($this->dataHelper->gets('Person', [
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
        /** @var array<int|string, DirectoryMapRow> $rows */
        $rows = $this->dataHelper->gets('Person', [
            'InPresentationDirectory' => 1,
            'Location IS NOT NULL' => null,
            'Inactivated' => 0
        ], 'Id, FirstName, LastName, NickName, Avatar, UseGravatar, Email, Location, '
            . 'MyPublicDataInPresentationDirectory, InPresentationDirectory, '
            . 'ShowPhoneInPresentationDirectory, ShowEmailInPresentationDirectory');
        $locationData = $this->getLocationData(array_values($rows));

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
        /** @var array<int|string, DirectoryMapRow> $rows */
        $rows = $this->dataHelper->gets('Person', [
            'InPresentationDirectory' => 1,
            'Location IS NOT NULL' => null,
            'Inactivated' => 0,
            'MyPublicDataInPresentationDirectory IS NOT NULL AND MyPublicDataInPresentationDirectory != ""' => null
        ], 'Id, FirstName, LastName, NickName, Avatar, UseGravatar, Email, Location, MyPublicDataInPresentationDirectory, '
            . 'InPresentationDirectory, ShowPhoneInPresentationDirectory, ShowEmailInPresentationDirectory');
        $locationData = $this->getLocationData(array_values($rows));

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

    #region Private functions
    /**
     * @param  list<DirectoryMapRow> $members
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     nickname: ?string,
     *     avatar: ?string,
     *     useGravatar: bool,
     *     email: string,
     *     lat: string,
     *     lng: string,
     *     userImg: string,
     *     myPublicData: ?string
     * }>
     */
    private function getLocationData(array $members): array
    {
        $gravatarHandler = new GravatarHandler();
        $locationData = [];
        foreach ($members as $member) {
            if (
                !empty($member->Location) && preg_match(
                    '/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/',
                    $member->Location
                )
            ) {
                list($lat, $lng) = explode(',', $member->Location);
                $locationData[] = [
                    'id' => (int) $member->Id,
                    'name' => $member->FirstName . ' ' . $member->LastName,
                    'nickname' => $member->NickName,
                    'avatar' => $member->Avatar,
                    'useGravatar' => (bool) $member->UseGravatar,
                    'email' => $member->Email,
                    'lat' => trim($lat),
                    'lng' => trim($lng),
                    'userImg' => WebApp::getUserImg(Person::fromRow($member), $gravatarHandler),
                    'myPublicData' => $member->MyPublicDataInPresentationDirectory
                ];
            }
        }
        return $locationData;
    }
}
