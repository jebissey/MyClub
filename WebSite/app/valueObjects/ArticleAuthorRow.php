<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class ArticleAuthorRow
{
    public function __construct(
        public int $Id,
        public string $PersonName,
        public string $ArticleTitle,
    ) {
    }

    /**
     * @param object{Id: int|string, PersonName: string, ArticleTitle: string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int)$row->Id,
            PersonName: $row->PersonName,
            ArticleTitle: $row->ArticleTitle,
        );
    }
}
