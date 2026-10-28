<?php

declare(strict_types=1);

namespace app\modules\User;

use stdClass;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\ParticipantDataHelper;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserConnectionsViewModel;
use app\modules\Common\valueObjects\Person;

/**
 * @phpstan-import-type PersonRow from Person
 */
final class UserConnectionsController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly ParticipantDataHelper $participantDataHelper,
    ) {
        parent::__construct($application);
    }

    public function showConnectionsOfConnectedUser(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }
        $this->showConnections($person->id);
    }

    public function showConnections(int $idPerson): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $individual = $this->dataHelper->get(
            'Individual',
            ['Id' => $idPerson],
            'Id, Email, FirstName, LastName, NickName'
        );
        $member = $this->dataHelper->get(
            'Member',
            ['Id' => $idPerson],
            'InPresentationDirectory, ShowPhoneInPresentationDirectory, ShowEmailInPresentationDirectory'
        );
        if ($individual === false || $member === false) {
            $this->errors->badRequest("User ({$idPerson}) not found", __FILE__, __LINE__);
            return;
        } else {
            $user = (object) array_merge(
                (array) $individual,
                (array) $member
            );
        }
        $user = (object) array_merge(
            (array) $individual,
            (array) $member
        );

/** @var stdClass&PersonRow $userRow */
        $userRow = $user;
        $person = Person::fromRow($userRow);
        $data = $this->participantDataHelper->getConnections($idPerson);

        $viewModel = new UserConnectionsViewModel(
            connections: $data['connections'],
            maxEvents: $data['maxEvents'],
            layout: $this->getLayout(),
            navItems: $this->getNavItems($person->email),
            user: $person->firstName . ' ' . $person->lastName . ($person->nickName != '' ? ' (' . $person->nickName . ')' : ''),
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/user_connections.latte', $viewModel->toArray());
    }
}
