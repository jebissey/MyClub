<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\News;
use app\modules\Common\AbstractShowController;
use app\modules\User\viewModels\UserNewsViewModel;

class UserNewsController extends AbstractShowController
{
    public function __construct(
        Application $application,
        private News $news,
    ) {
        parent::__construct($application);
    }

    public function showNews(): void
    {
        $connectedUser = $this->requireConnectedPerson();
        if ($connectedUser === false) {
            return;
        }

        [$searchMode, $searchFrom] = $this->resolveSearchPeriod(
            $connectedUser,
            default: date('Y-m-d H:i:s', strtotime('-1 day'))
        );

        $viewModel = new UserNewsViewModel(
            searchFrom: $searchFrom,
            searchMode: $searchMode,
            navItems: $this->getNavItems($connectedUser->person),
            person: $connectedUser->person,
            news: $this->news->getNewsForPerson($connectedUser, $searchFrom),
            btnParent: '/user',
            layoutParams: $this->getAllParams([]),
        );

        $this->render('User/views/news.latte', $viewModel->toArray());
    }
}
