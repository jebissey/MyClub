<?php

declare(strict_types=1);

namespace app\valueObjects;

/** Ligne OrderReply ou Reply (réponses en JSON) */
final readonly class AnswersRow
{
    public function __construct(
        public string $Answers,
    ) {
    }

    /**
     * @param object{Answers: string} $o
     */
    public static function fromStdClass(object $o): self
    {
        return new self((string)$o->Answers);
    }
}
