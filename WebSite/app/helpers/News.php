<?php

namespace app\helpers;

class News
{
    private array $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function getNewsForPerson($person, $searchFrom): array
    {
        $news = [];
        foreach ($this->providers as $provider) {
            $news = array_merge($news, $provider->getNews($person, $searchFrom));
        }
        return $news;
    }

    public function anyNews($person): bool
    {
        $news = $this->getNewsForPerson($person, $person->LastSignIn ?? '');
        return is_array($news) && count($news) > 0;
    }
}
