<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\models\ParticipantDataHelper;
use app\modules\Common\AbstractController;

class UserConnectionsController extends AbstractController
{
    public function __construct(
        Application $application,
        private ParticipantDataHelper $participantDataHelper,
    ) {
        parent::__construct($application);
    }

    public function showConnections_(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        $this->showConnections($person->Id);
    }

    public function showConnections(int $idPerson): void
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
        $data = $this->participantDataHelper->getConnections($idPerson);
        $user = $this->dataHelper->get('Person', ['Id' => $idPerson], 'FirstName, LastName, NickName');
        $this->render('User/views/user_connections.latte', $this->getAllParams([
            'connections' => $data['connections'],
            'maxEvents'   => $data['maxEvents'],
            'layout' => $this->getLayout(),
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'page' => $this->application->getConnectedUser()->getPage(1),
            'user' => $user->FirstName . ' ' . $user->LastName . ($user->NickName != '' ? ' (' . $user->NickName . ')' : ''),
        ]));
    }
}
