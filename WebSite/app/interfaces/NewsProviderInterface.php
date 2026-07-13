<?php

declare(strict_types=1);

namespace app\interfaces;

use app\helpers\ConnectedUser;

interface NewsProviderInterface
{
    /**
     * @return array<int, mixed>
     */
    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array;
}
