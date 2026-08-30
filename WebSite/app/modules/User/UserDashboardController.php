<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\User\viewModels\UserDashboardViewModel;

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
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $lang = TranslationManager::getCurrentLanguage();
        $helpRow = $this->dataHelper->get('Languages', ['Name' => 'Help_User'], $lang);
        $content = ($helpRow !== false && isset($helpRow->$lang)) ? $helpRow->$lang : '';

        $viewModel = new InfoViewModel(
            content: $content,
            hasAuthorization: $this->application->getConnectedUser()->hasAutorization(),
            timer: 0,
            previousPage: true,
            layoutParams: $this->getAllParams([]),
        );
        $this->render('Common/views/info.latte', $viewModel->toArray());
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
