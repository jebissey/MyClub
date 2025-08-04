<?php

namespace app\helpers;

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
        foreach ($this->providers as $provider) {
            $news = array_merge($news, $provider->getNews($connectedUser, $searchFrom));
        }
        return $news;
    }

    public function anyNews(ConnectedUser $connectedUser): bool
    {
        $news = $this->getNewsForPerson($connectedUser, $person->LastSignIn ?? '');
        return is_array($news) && count($news) > 0;
    }
}
