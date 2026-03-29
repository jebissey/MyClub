<?php

declare(strict_types=1);

namespace app\modules\Communication;

use app\helpers\Application;
use app\modules\Common\AbstractController;

class CommunicationController extends AbstractController
{
    public function __construct(
        Application $application,
    ) {
        parent::__construct($application);
    }

    public function edit(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isCommunicationManager())) {
            $connectedUser = $this->application->getConnectedUser();

            $this->render('Communication/views/communication_edit.latte', $this->getAllParams([
                'groups'          => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'navItems'        => $this->getNavItems($connectedUser->person ?? false),
                'page'            => $connectedUser->getPage(),
                'btn_HistoryBack' => true,
            ]));
        }
    }
}
