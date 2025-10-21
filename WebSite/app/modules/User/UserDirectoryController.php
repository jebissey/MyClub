<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;

class UserDirectoryController extends AbstractController
{
    public function __construct(
        Application $application,
        private PersonDataHelper $personDataHelper,
        private GroupDataHelper $groupDataHelper
    ) {
        parent::__construct($application);
    }

    public function showDirectory()
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $groupParam = $this->flight->request()->query['group'] ?? null;
        $selectedGroup = ($groupParam !== null && ctype_digit((string)$groupParam)) ? (int)$groupParam : null;
        if ($selectedGroup) $persons = $this->personDataHelper->getPersonsInGroupForDirectory($selectedGroup);
        else {
            $persons = $this->dataHelper->gets('Person', [
                'InPresentationDirectory' => 1,
                'Inactivated' => 0
            ], 'Id, LastName, FirstName, NickName, UseGravatar, Avatar, Email, InPresentationDirectory', 'FirstName, LastName');
            $gravatarHandler = new GravatarHandler();
            foreach ($persons as $person) {
                $person->UserImg = WebApp::getUserImg($person, $gravatarHandler);
            }
        }
        $groupCounts = $this->groupDataHelper->getGroupCount();
        $this->render('User/views/users_directory.latte', Params::getAll([
            'persons' => $persons,
            'navItems' => $this->getNavItems($person),
            'loggedPerson' => $person,
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'groupCounts' => $groupCounts,
            'selectedGroup' => $selectedGroup,
            'page' => $this->application->getConnectedUser()->getPage(1),
        ]));
    }

    public function showMap()
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $members = $this->dataHelper->gets('Person', [
            'InPresentationDirectory' => 1,
            'Location IS NOT NULL' => null,
            'Inactivated' => 0
        ]);
        $gravatarHandler = new GravatarHandler();
        foreach ($members as $member) {
            $member->UserImg = WebApp::getUserImg($member, $gravatarHandler);
        }
        $locationData = [];
        foreach ($members as $member) {
            if (!empty($member->Location) && preg_match('/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/', $member->Location)) {
                list($lat, $lng) = explode(',', $member->Location);
                $locationData[] = [
                    'id' => $member->Id,
                    'name' => $member->FirstName . ' ' . $member->LastName,
                    'nickname' => $member->NickName,
                    'avatar' => $member->Avatar,
                    'useGravatar' => $member->UseGravatar,
                    'email' => $member->Email,
                    'lat' => trim($lat),
                    'lng' => trim($lng),
                    'userImg' => $member->UserImg,
                ];
            }
        }

        $this->render('User/views/users_map.latte', Params::getAll([
            'locationData' => $locationData,
            'membersCount' => count($locationData),
            'navItems' => $this->getNavItems($person),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }
}
