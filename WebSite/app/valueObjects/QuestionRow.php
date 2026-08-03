<?php

declare(strict_types=1);

namespace app\valueObjects;

/** Ligne Order ou Survey (Id, Question, Options en JSON) */
final readonly class QuestionRow
{
    public function __construct(
        public int $Id,
        public string $Question,
        public string $Options,
    ) {
    }

    /**
     * @param object{Id: int|string, Question: string, Options: string} $o
     */
    public static function fromStdClass(object $o): self
    {
        return new self(
            (int)$o->Id,
            (string)$o->Question,
            (string)$o->Options,
        );
    }
}
