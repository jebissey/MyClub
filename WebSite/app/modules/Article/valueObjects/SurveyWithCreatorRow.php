<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;
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
        public int $id,
        public string $question,
        public string $options,
        public int $idArticle,
        public string $closingDate,
        public SurveyVisibility $visibility,
        public int $createdBy,
    ) {
    }

    /** @param SurveyWithCreatorRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            question: $row->Question,
            options: $row->Options,
            idArticle: (int) $row->IdArticle,
            closingDate: $row->ClosingDate,
            visibility: SurveyVisibility::from($row->Visibility),
            createdBy: (int) $row->CreatedBy,
        );
    }
}
