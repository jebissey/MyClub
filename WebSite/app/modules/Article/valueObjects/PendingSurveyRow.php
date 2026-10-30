<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

/**
 * @phpstan-type PendingSurveyRowShape object{
 *     PersonId: int|string,
 *     Email: string,
 *     ArticleId: int|string,
 *     ArticleTitle: string,
 *     SurveyId: int|string,
 *     SurveyQuestion: string,
 *     ClosingDate: string
 * }
 */
final readonly class PendingSurveyRow
{
    public function __construct(
        public int $personId,
        public string $email,
        public int $articleId,
        public string $articleTitle,
        public int $surveyId,
        public string $surveyQuestion,
        public string $closingDate,
    ) {
    }

    /**
     * @param PendingSurveyRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            personId: (int) $row->PersonId,
            email: (string) $row->Email,
            articleId: (int) $row->ArticleId,
            articleTitle: (string) $row->ArticleTitle,
            surveyId: (int) $row->SurveyId,
            surveyQuestion: (string) $row->SurveyQuestion,
            closingDate: (string) $row->ClosingDate,
        );
    }
}
