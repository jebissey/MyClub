<?php

declare(strict_types=1);

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\News;
use app\modules\Common\AbstractShowController;
use app\modules\User\viewModels\UserNewsViewModel;

final class UserNewsController extends AbstractShowController
{
    /** @var array<string, array{label: string, icon: string, bg: string}> */
    private const GROUP_CONFIG = [
        'article'      => ['label' => 'user.news.group.articles',      'icon' => 'bi-file-text',      'bg' => 'primary'],
        'event'        => ['label' => 'user.news.group.events',        'icon' => 'bi-calendar-event', 'bg' => 'success'],
        'message'      => ['label' => 'user.news.group.messages',      'icon' => 'bi-envelope',       'bg' => 'info'],
        'presentation' => ['label' => 'user.news.group.presentations', 'icon' => 'bi-person-badge',   'bg' => 'warning'],
        'survey'       => ['label' => 'user.news.group.surveys',       'icon' => 'bi-bar-chart',      'bg' => 'secondary'],
        'order'        => ['label' => 'user.news.group.orders',        'icon' => 'bi-cart-check',     'bg' => 'warning'],
    ];

    public function __construct(
        Application $application,
        private readonly News $news,
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
            default: date('Y-m-d H:i:s', strtotime('-1 day')),
            cookieName: 'period_user_news',
        );

        $viewModel = new UserNewsViewModel(
            searchFrom: $searchFrom,
            searchMode: $searchMode,
            navItems: $this->getNavItems($connectedUser->person?->email),
            person: $connectedUser->person,
            news: $this->news->getNewsForPerson($connectedUser, $searchFrom),
            groupConfig: self::GROUP_CONFIG,
            btnParent: '/user',
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/news.latte', $viewModel);
    }
}
