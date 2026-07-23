<?php

declare(strict_types=1);

namespace app\valueObjects;

use app\enums\SurveyVisibility;

/**
 * Ligne Survey enrichie du créateur de l'article associé,
 * telle que retournée par SurveyDataHelper::getWithCreator().
 *
 * @phpstan-type SurveyWithCreatorRowShape object{
 *     Id: int|string,
 *     Question: string,
 *     Options: string,
 *     IdArticle: int|string,
 *     ClosingDate: string,
 *     Visibility: string,
 *     CreatedBy: int|string
 * }
 */
final readonly class SurveyWithCreatorRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public string $Question,
        public string $Options,
        public int $IdArticle,
        public string $ClosingDate,
        public SurveyVisibility $Visibility,
        public int $CreatedBy,
    ) {
    }

    /** @param SurveyWithCreatorRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Question: $row->Question,
            Options: $row->Options,
            IdArticle: (int) $row->IdArticle,
            ClosingDate: $row->ClosingDate,
            Visibility: SurveyVisibility::from($row->Visibility),
            CreatedBy: (int) $row->CreatedBy,
        );
    }
}
