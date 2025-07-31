<?php

namespace app\interfaces;

interface NewsProviderInterface
{
    public function getNews(object $person, string $searchFrom): array;
}

