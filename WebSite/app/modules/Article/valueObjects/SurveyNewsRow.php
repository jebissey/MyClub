<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;
use app\enums\SurveyVisibility;

/**
 * @phpstan-type SurveyNewsRowShape object{
 *     FirstName: string,
 *     LastName: string,
 *     SurveyId: int|string,
 *     Question: string,
 *     ClosingDate: string,
 *     Visibility: string,
 *     IdArticle: int|string,
 *     CreatedBy: int|string,
 *     PublishedBy: int|string|null,
 *     IdGroup: int|string|null,
 *     OnlyForMembers: int|bool|null,
 *     LastActivity: string,
 *     Voters: string
 * }
 */
final readonly class SurveyNewsRow extends AbstractValueObject
{
    public function __construct(
        public string $FirstName,
        public string $LastName,
        public int $SurveyId,
        public string $Question,
        public string $ClosingDate,
        public SurveyVisibility $Visibility,
        public int $IdArticle,
        public int $CreatedBy,
        public ?int $PublishedBy,
        public ?int $IdGroup,
        public bool $OnlyForMembers,
        public string $LastActivity,
        public string $Voters,
    ) {
    }

    /** @param SurveyNewsRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            FirstName: $row->FirstName,
            LastName: $row->LastName,
            SurveyId: (int) $row->SurveyId,
            Question: $row->Question,
            ClosingDate: $row->ClosingDate,
            Visibility: SurveyVisibility::from($row->Visibility),
            IdArticle: (int) $row->IdArticle,
            CreatedBy: (int) $row->CreatedBy,
            PublishedBy: $row->PublishedBy === null ? null : (int) $row->PublishedBy,
            IdGroup: $row->IdGroup === null ? null : (int) $row->IdGroup,
            OnlyForMembers: (bool) $row->OnlyForMembers,
            LastActivity: $row->LastActivity,
            Voters: $row->Voters,
        );
    }
}
