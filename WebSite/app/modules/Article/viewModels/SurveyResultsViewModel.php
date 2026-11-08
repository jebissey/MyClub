<?php

declare(strict_types=1);

namespace app\modules\Article\viewModels;

use app\modules\Article\valueObjects\SurveyWithCreatorRow;

final readonly class SurveyResultsViewModel extends ClosableFormResultsViewModel
{
    /**
     * @param list<string> $options
     * @param array<string, int> $results
     * @param list<array{name: string, answers: list<string>}> $participants
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public SurveyWithCreatorRow $survey, // the type returned by SurveyDataHelper::getWithCreator()
        public array $options,
        public array $results,
        public array $participants,
        int $articleId,
        array $layoutParams,
    ) {
        parent::__construct($articleId, $layoutParams);
    }
}
