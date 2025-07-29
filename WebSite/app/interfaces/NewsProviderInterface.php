<?php

namespace app\interfaces;

interface NewsProviderInterface
{
    public function getNews($person, $searchFrom): array;
}

