<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

final readonly class ArticleAuthorRow
{
    public function __construct(
        public int $id,
        public string $personName,
        public string $articleTitle,
    ) {
    }

    /**
     * @param object{Id: int|string, PersonName: string, ArticleTitle: string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int)$row->Id,
            personName: $row->PersonName,
            articleTitle: $row->ArticleTitle,
        );
    }
}
