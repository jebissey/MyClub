<?php

declare(strict_types=1);

namespace app\modules\Exercise\valueObjects;

final readonly class ExerciseRow
{
    public function __construct(
        public int $id,
        public string $content,
        public string $title,
        public string $lastUpdate,
    ) {
    }

    /**
     * @param object{Id: int|string, Content: string, Title: string, LastUpdate: string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int)$row->Id,
            content: $row->Content,
            title: $row->Title,
            lastUpdate: $row->LastUpdate,
        );
    }
}
