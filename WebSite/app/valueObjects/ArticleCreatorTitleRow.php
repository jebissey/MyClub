<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class ArticleCreatorTitleRow
{
    public function __construct(
        public int $CreatedBy,
        public string $Title,
    ) {
    }

    /**
     * @param object{CreatedBy:int|string, Title:string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            CreatedBy: (int) $row->CreatedBy,
            Title: (string) $row->Title,
        );
    }
}
