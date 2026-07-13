<?php

declare(strict_types=1);

namespace app\helpers;

use app\interfaces\NewsProviderInterface;

class News
{
    /**
     * @param array<int, NewsProviderInterface> $providers
     */
    public function __construct(private array $providers)
    {
    }

    /**
     * @return array<int, mixed>
     */
    public function getNewsForPerson(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if ($connectedUser->person ?? false) {
            foreach ($this->providers as $provider) {
                $news = array_merge($news, $provider->getNews($connectedUser, $searchFrom));
            }
        }
        return $news;
    }

    public function anyNews(ConnectedUser $connectedUser): bool
    {
        $news = $this->getNewsForPerson($connectedUser, $connectedUser->person->LastSignIn ?? '');
        return count($news) > 0;
    }
}
