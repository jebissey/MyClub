<?php

declare(strict_types=1);

namespace app\models\interfaces;

interface CarouselDataHelperInterface
{
    /**
     * @param array{id?: int|string, idArticle: int|string} $data
     */
    public function addOrUpdate(array $data, string $item): string;
}
