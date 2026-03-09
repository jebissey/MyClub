<?php

declare(strict_types=1);

namespace app\interfaces;

use app\helpers\ConnectedUser;

interface NewsProviderInterface
{
    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array;
}
