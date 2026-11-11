<?php

declare(strict_types=1);

namespace app\modules\Common\interfaces;

use app\helpers\ConnectedUser;

/**
 * @phpstan-type NewsFeedItem = array{
 *     type: 'article'|'event'|'message'|'order'|'presentation'|'survey',
 *     id: int,
 *     title: string,
 *     date: string,
 *     url: string,
 *     from?: string,
 *     detail?: string
 * }
 */
interface NewsProviderInterface
{
    /**
     * @return list<NewsFeedItem>
     */
    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array;
}