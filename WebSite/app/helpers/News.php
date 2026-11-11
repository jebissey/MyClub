<?php

declare(strict_types=1);

namespace app\helpers;

use app\modules\Common\interfaces\NewsProviderInterface;

/**
 * @phpstan-import-type NewsFeedItem from NewsProviderInterface
 */
final readonly class News
{
    /**
     * @param list<NewsProviderInterface> $providers
     */
    public function __construct(private array $providers)
    {
    }

    /**
     * @return list<NewsFeedItem>
     */
    public function getNewsForPerson(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];

        if ($connectedUser->person !== null) {
            foreach ($this->providers as $provider) {
                array_push(
                    $news,
                    ...$provider->getNews($connectedUser, $searchFrom)
                );
            }
        }

        return $news;
    }

    public function anyNews(ConnectedUser $connectedUser): bool
    {
        return $this->getNewsForPerson(
            $connectedUser,
            $connectedUser->getLastSignIn() ?? ''
        ) !== [];
    }
}
