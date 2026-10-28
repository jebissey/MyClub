<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\enums\SurveyVisibility;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\ArticleDataHelper;
use app\models\SurveyDataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleRow;
use app\modules\Common\valueObjects\ClosingVisibilityRow;

final class SurveyController extends ClosableFormController
{
    public function __construct(
        Application $application,
        ArticleDataHelper $articleDataHelper,
        ArticleAuthorizationService $authorizationService,
        private readonly SurveyDataHelper $surveyDataHelper,
    ) {
        parent::__construct($application, $articleDataHelper, $authorizationService);
    }

    protected function formName(): string
    {
        return 'survey';
    }

    protected function visibilityEnum(): string
    {
        return SurveyVisibility::class;
    }

    protected function defaultVisibility(): string
    {
        return SurveyVisibility::Redactor->value;
    }

    protected function canReadResults(ClosingVisibilityRow $form, ArticleRow $article, ConnectedUser $user): bool
    {
        return $this->authorizationService->canReadSurveyResults($form, $article, $user);
    }

    protected function buildResultsParams(int $articleId): ?array
    {
        $survey = $this->surveyDataHelper->getWithCreator($articleId);
        if ($survey === false) {
            return null;
        }

        $replies = $this->surveyDataHelper->getRepliesForActivePersons($survey->id);
        $participants = [];
        /** @var array<string, int> $results */
        $results = [];

        /** @var array<string> $options */
        $options = json_decode($survey->options) ?? [];
        foreach ($options as $option) {
            $results[$option] = 0;
        }

        foreach ($replies as $reply) {
            /** @var array<string> $answers */
            $answers = json_decode($reply->answers) ?? [];

            $participants[] = [
                'name'    => $this->participantName($reply->idPerson),
                'answers' => $answers,
            ];

            foreach ($answers as $answer) {
                if (isset($results[$answer])) {
                    $results[$answer]++;
                }
            }
        }

        return [
            'survey'       => $survey,
            'options'      => $options,
            'results'      => $results,
            'participants' => $participants,
        ];
    }
}
