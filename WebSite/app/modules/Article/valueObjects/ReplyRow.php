<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

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
        public int $id,
        public int $idPerson,
        public int $idSurvey,
        public string $answers,
        public string $lastUpdate,
    ) {
    }

    /** @param ReplyRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            idPerson: (int) $row->IdPerson,
            idSurvey: (int) $row->IdSurvey,
            answers: $row->Answers,
            lastUpdate: $row->LastUpdate,
        );
    }
}
