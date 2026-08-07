<?php

declare(strict_types=1);

namespace app\modules\Article;

use DateTime;
use app\enums\FilterInputRule;
use app\enums\SurveyVisibility;
use app\exceptions\IntegrityException;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\SurveyDataHelper;
use app\modules\Common\AbstractController;
use app\valueObjects\ArticleAuthorizationRow;
use app\valueObjects\ArticleRow;
use app\valueObjects\ArticleTitleRow;
use app\valueObjects\IdRow;
use app\valueObjects\PersonNameRow;

class SurveyController extends AbstractController
{
    public function __construct(Application $application, private SurveyDataHelper $surveyDataHelper)
    {
        parent::__construct($application);
    }

    public function add(int $articleId): void
    {
        if (!$this->application->getConnectedUser()->isRedactor()) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $articleData = $this->dataHelper->get('Article', ['Id' => $articleId], 'Title, Id');
        if (!$articleData) {
            $this->redirect('/articles');
            return;
        }
        /** @var object{Id: int|string, Title: string} $articleData */
        $article = ArticleTitleRow::fromStdClass($articleData);
        $this->render('Article/views/survey_add.latte', $this->getAllParams([
            'article' => $article,
            'survey' => $this->dataHelper->get('Survey', ['IdArticle' => $article->Id], 'Question, Options, ClosingDate, Visibility'),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function createOrUpdate(): void
    {
        if (!$this->application->getConnectedUser()->isRedactor()) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $schema = [
            'article_id' => FilterInputRule::Int->value,
            'question' => FilterInputRule::HtmlSafeText->value,
            'closingDate' => FilterInputRule::DateTime->value,
            'visibility' => $this->application->enumToValues(SurveyVisibility::class),
            'options' => FilterInputRule::ArrayString->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        /** @var int $articleId */
        $articleId = $input['article_id'] ?? throw new IntegrityException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
        $question = $input['question'] ?? '???';
        $closingDate = $input['closingDate'] ?? new DateTime('+7 days');
        $visibility = $input['visibility'] ?? SurveyVisibility::Redactor->value;
        /** @var array<string> $rawOptions */
        $rawOptions = $input['options'] ?? [];
        $options = [];
        foreach ($rawOptions as $option) {
            $options[] = str_replace('"', "''", $option);
        }
        $optionsJson = json_encode($options);
        $fields = [
            'Question' => $question,
            'Options' => $optionsJson,
            'ClosingDate' => $closingDate,
            'IdArticle' => $articleId,
            'Visibility' => $visibility
        ];
        $surveyData = $this->dataHelper->get('Survey', ['IdArticle' => $articleId], 'Id');
        if ($surveyData) {
            /** @var object{Id: int|string} $surveyData */
            $survey = IdRow::fromStdClass($surveyData);
            $this->dataHelper->set('Survey', $fields, ['Id' => $survey->Id]);
        } else {
            $this->dataHelper->set('Survey', $fields);
        }
        $this->redirect('/article/' . $articleId);
    }

    public function viewResults(int $articleId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        if ($this->dataHelper->get('Article', ['Id' => $articleId], 'Id') === false) {
            $this->raiseBadRequest("Article {$articleId} doesn't exist", __FILE__, __LINE__);
            return;
        }

        $connectedUser = $this->application->getConnectedUser();

        if ($connectedUser->person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }

        $survey = $this->surveyDataHelper->getWithCreator($articleId);

        if ($survey === false) {
            $this->raiseBadRequest("No survey for article {$articleId}", __FILE__, __LINE__);
            $this->redirect('/article/' . $articleId);
            return;
        }

        $articleForAuth = $this->dataHelper->get(
            'Article',
            ['Id' => $survey->IdArticle],
            'Id, Title, Content, CreatedBy, PublishedBy, IdGroup, OnlyForMembers, LastUpdate'
        );

        if ($articleForAuth === false) {
            $this->raiseBadRequest("Article {$survey->IdArticle} doesn't exist", __FILE__, __LINE__);
            return;
        }

        /** @var object{
        * Id: int|string,
        * Title: string,
        * Content: string,
        * CreatedBy: int|string,
        * PublishedBy?: int|string|null,
        * IdGroup?: int|string|null,
        * OnlyForMembers?: bool|int|null,
        * LastUpdate: string
        * } $articleForAuth */
        $article = ArticleAuthorizationRow::fromStdClass(ArticleRow::fromStdClass($articleForAuth));

        if ($this->authorizationDataHelper->canPersonReadSurveyResults($article, $connectedUser)) {
            $replies = $this->surveyDataHelper->getRepliesForActivePersons($survey->Id);
            $participants = [];
            /** @var array<string, int> $results */
            $results = [];

            /** @var array<string> $options */
            $options = json_decode($survey->Options) ?? [];

            foreach ($options as $option) {
                $results[$option] = 0;
            }

            foreach ($replies as $reply) {
                /** @var array<string> $answers */
                $answers = json_decode($reply->Answers) ?? [];

                $personData = $this->dataHelper->get(
                    'Person',
                    ['Id' => $reply->IdPerson],
                    'FirstName, LastName'
                );

                /** @var object{FirstName: string, LastName: string}|false $personData */
                $person = $personData ? PersonNameRow::fromStdClass($personData) : null;

                $participants[] = [
                    'name' => $person === null ? '???' : $person->FirstName . ' ' . $person->LastName,
                    'answers' => $answers
                ];

                foreach ($answers as $answer) {
                    if (isset($results[$answer])) {
                        $results[$answer]++;
                    }
                }
            }

            $this->render('Article/views/survey_results.latte', $this->getAllParams([
                'survey' => $survey,
                'options' => $options,
                'results' => $results,
                'participants' => $participants,
                'articleId' => $articleId,
                'currentVersion' => Application::VERSION,
                'page' => $connectedUser->getPage(),
                'btn_HistoryBack' => true,
                'btn_Parent' => "/article/{$articleId}",
            ]));
        } else {
            $this->raiseForbidden(__FILE__, __LINE__);
        }
    }
}
