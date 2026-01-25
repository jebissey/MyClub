<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\modules\Common\AbstractController;

class UserDashboardController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function help(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Common/views/info.latte', $this->getAllParams([
            'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_User'], 'Value')->Value ?? '',
            'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization(),
            'currentVersion' => Application::VERSION,
            'timer' => 0,
            'previousPage' => true,
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function user(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $_SESSION['navbar'] = 'user';
        $this->render('User/views/user.latte', $this->getAllParams([
            'page' => ''
        ]));
    }
}
