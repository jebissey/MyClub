<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\User\viewModels\UserDashboardViewModel;

final class UserDashboardController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function help(): void
    {
        $this->renderHelp('Help_User', fn($u) => $u->isConnected(), __FILE__, __LINE__);
    }

    public function user(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $_SESSION['navbar'] = 'user';

        $viewModel = new UserDashboardViewModel(
            content: ($this->t)('User'),
            layoutParams: $this->getAllParams([]),
        );
        $this->render('User/views/user.latte', $viewModel->toArray());
    }
}
