<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\WebApp;
use app\models\ParticipantDataHelper;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserConnectionsViewModel;
use app\valueObjects\Person;

/**
 * @phpstan-import-type PersonRow from Person
 */
class UserConnectionsController extends AbstractController
{
    public function __construct(
        Application $application,
        private ParticipantDataHelper $participantDataHelper,
    ) {
        parent::__construct($application);
    }

    public function showConnectionsOfConnectedUser(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        $this->showConnections($person->Id);
    }

    public function showConnections(int $idPerson): void
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
        $user = $this->dataHelper->get(
            'Person',
            ['Id' => $idPerson],
            'FirstName, LastName, NickName, Id, Email, InPresentationDirectory, '
                . 'ShowPhoneInPresentationDirectory, ShowEmailInPresentationDirectory'
        );
        if ($user === false) {
            $this->raiseBadRequest("User ({$idPerson}) not found", __FILE__, __LINE__);
            return;
        }
        /** @var PersonRow $userRow */
        $userRow = $user;
        $person = Person::fromRow($userRow);
        $data = $this->participantDataHelper->getConnections($idPerson);

        $viewModel = new UserConnectionsViewModel(
            connections: $data['connections'],
            maxEvents: $data['maxEvents'],
            layout: $this->getLayout(),
            navItems: $this->getNavItems($person),
            user: $person->FirstName . ' ' . $person->LastName . ($person->NickName != '' ? ' (' . $person->NickName . ')' : ''),
            layoutParams: $this->getAllParams([]),
        );

        $this->render('User/views/user_connections.latte', $viewModel->toArray());
    }
}
