<?php

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\Params;
use app\modules\Common\AbstractController;

class UserDashboardController extends AbstractController
{
    public function user(): void
    {
        if ($this->application->getConnectedUser()->get()->person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $_SESSION['navbar'] = 'user';
        $this->render('User/views/user.latte', Params::getAll(['page' => '']));
    }

    public function help(): void
    {
        if ($this->application->getConnectedUser()->get()->person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Common/views/info.latte', Params::getAll([
            'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_user'], 'Value')->Value ?? '',
            'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization(),
            'currentVersion' => Application::VERSION,
            'timer' => 0,
            'previousPage' => true
        ]));
    }
}
