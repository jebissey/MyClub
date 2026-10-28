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
        public string $firstName,
        public string $lastName,
        public int $surveyId,
        public string $question,
        public string $closingDate,
        public SurveyVisibility $visibility,
        public int $idArticle,
        public int $createdBy,
        public ?int $publishedBy,
        public ?int $idGroup,
        public bool $onlyForMembers,
        public string $lastActivity,
        public string $voters,
    ) {
    }

    /** @param SurveyNewsRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            firstName: $row->FirstName,
            lastName: $row->LastName,
            surveyId: (int) $row->SurveyId,
            question: $row->Question,
            closingDate: $row->ClosingDate,
            visibility: SurveyVisibility::from($row->Visibility),
            idArticle: (int) $row->IdArticle,
            createdBy: (int) $row->CreatedBy,
            publishedBy: $row->PublishedBy === null ? null : (int) $row->PublishedBy,
            idGroup: $row->IdGroup === null ? null : (int) $row->IdGroup,
            onlyForMembers: (bool) $row->OnlyForMembers,
            lastActivity: $row->LastActivity,
            voters: $row->Voters,
        );
    }
}
