<?php

namespace app\helpers;

use RuntimeException;

class News
{
    private array $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function getNewsForPerson(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        foreach ($this->providers as $provider) $news = array_merge($news, $provider->getNews($connectedUser, $searchFrom));
        return $news;
    }

    public function anyNews(ConnectedUser $connectedUser): bool
    {
        $news = $this->getNewsForPerson($connectedUser, $connectedUser->person->LastSignIn ?? throw new RuntimeException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__));
        return is_array($news) && count($news) > 0;
    }
}
