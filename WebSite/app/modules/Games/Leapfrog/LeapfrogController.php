<?php

declare(strict_types=1);

namespace app\modules\Games\Leapfrog;

use app\helpers\Application;
use app\helpers\Params;
use app\modules\Common\AbstractController;

class LeapfrogController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function play(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        $positions = [
            ['type' => 'bergerie', 'id' => 1],
            ['type' => 'bergerie', 'id' => 2],
            ['type' => 'bergerie', 'id' => 3],
            ['type' => 'vide', 'id' => 4],
            ['type' => 'paturage', 'id' => 5],
            ['type' => 'paturage', 'id' => 6],
            ['type' => 'paturage', 'id' => 7]
        ];

        $this->render('Games/Leapfrog/views/leapfrog.latte', Params::getAll([
            'navItems' => $this->getNavItems($connectedUser->person),
            'page' => $this->application->getConnectedUser()->getPage(),
            'titre' => "Saute-Mouton",
            'positions' => $positions,
            'sessionId' => session_id()
        ]));
    }
}
