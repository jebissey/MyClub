<?php
declare(strict_types=1);

namespace app\helpers;

class News
{
    public function __construct(private array $providers) {}

    public function getNewsForPerson(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if ($connectedUser->person ?? false) {
            foreach ($this->providers as $provider) $news = array_merge($news, $provider->getNews($connectedUser, $searchFrom));
        }
        return $news;
    }

    public function anyNews(ConnectedUser $connectedUser): bool
    {
        $news = $this->getNewsForPerson($connectedUser, $connectedUser->person->LastSignIn ?? '');
        return is_array($news) && count($news) > 0;
    }
}
