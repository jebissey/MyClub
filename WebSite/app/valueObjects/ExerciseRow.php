<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class ExerciseRow
{
    public function __construct(
        public int $Id,
        public string $Content,
        public string $Title,
        public string $LastUpdate,
    ) {
    }

    /**
     * @param object{Id: int|string, Content: string, Title: string, LastUpdate: string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int)$row->Id,
            Content: $row->Content,
            Title: $row->Title,
            LastUpdate: $row->LastUpdate,
        );
    }
}
