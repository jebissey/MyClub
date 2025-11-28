<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\News;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

class UserNewsController extends AbstractController
{
    public function __construct(
        Application $application,
        private News $news,
    ) {
        parent::__construct($application);
    }

    public function showNews(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        if ($connectedUser->person ?? false) {
            $searchMode = WebApp::getFiltered('from', $this->application->enumToValues(\app\enums\Period::class), $this->flight->request()->query->getData()) ?: \app\enums\Period::Signout->value;

            if ($searchMode === \app\enums\Period::Signin->value) $searchFrom = $connectedUser->person->LastSignIn ?? '';
            elseif ($searchMode === \app\enums\Period::Signout->value) $searchFrom = $connectedUser->person->LastSignOut ?? '';
            elseif ($searchMode === \app\enums\Period::Week->value)    $searchFrom = date('Y-m-d H:i:s', strtotime('-1 week'));
            elseif ($searchMode === \app\enums\Period::Month->value)   $searchFrom = date('Y-m-d H:i:s', strtotime('-1 month'));
            elseif ($searchMode === \app\enums\Period::Quarter->value) $searchFrom = date('Y-m-d H:i:s', strtotime('-3 months'));
            elseif ($searchMode === \app\enums\Period::Year->value)    $searchFrom = date('Y-m-d H:i:s', strtotime('-1 year'));
            else $searchFrom = date('Y-m-d H:i:s', strtotime('-1 day'));

            $this->render('User/views/news.latte', $this->getAllParams([
                'news' => $this->news->getNewsForPerson($connectedUser, $searchFrom),
                'searchFrom' => $searchFrom,
                'searchMode' => $searchMode,
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
                'person' => $connectedUser->person,
                'page' => $connectedUser->getPage(1),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
