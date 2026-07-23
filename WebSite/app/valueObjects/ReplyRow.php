<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * Représentation fortement typée d'une réponse à un sondage (table Reply).
 *
 * @phpstan-type ReplyRowShape object{
 *     Id: int|string,
 *     IdPerson: int|string,
 *     IdSurvey: int|string,
 *     Answers: string,
 *     LastUpdate: string
 * }
 */
final readonly class ReplyRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public int $IdPerson,
        public int $IdSurvey,
        public string $Answers,
        public string $LastUpdate,
    ) {
    }

    /** @param ReplyRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            IdPerson: (int) $row->IdPerson,
            IdSurvey: (int) $row->IdSurvey,
            Answers: $row->Answers,
            LastUpdate: $row->LastUpdate,
        );
    }
}
