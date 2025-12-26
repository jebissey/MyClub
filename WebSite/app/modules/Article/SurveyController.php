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

class SurveyController extends AbstractController
{
    public function __construct(Application $application, private SurveyDataHelper $surveyDataHelper)
    {
        parent::__construct($application);
    }

    public function add(int $articleId): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $articleId], 'Title, Id');
        if (!$article) {
            $this->redirect('/articles');
            return;
        }
        $this->render('Article/views/survey_add.latte', $this->getAllParams([
            'article' => $article,
            'survey' => $this->dataHelper->get('Survey', ['IdArticle' => $article->Id], 'Question, Options, ClosingDate, Visibility'),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function createOrUpdate()
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
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
            'options' => FilterInputRule::ArrayString,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $articleId = $input['article_id'] ?? throw new IntegrityException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
        $question = $input['question'] ?? '???';
        $closingDate = $input['closingDate'] ?? new DateTime('+7 days');
        $visibility = $input['visibility'] ?? SurveyVisibility::Redactor->value;
        $options = [];
        foreach ($input['options'] ?? [] as $option) {
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
        $survey = $this->dataHelper->get('Survey', ['IdArticle' => $articleId], 'Id');
        if ($survey) $this->dataHelper->set('Survey', $fields, ['Id' => $survey->Id]);
        else         $this->dataHelper->set('Survey', $fields);
        $this->redirect('/article/' . $articleId);
    }

    public function viewResults($articleId)
    {
        if ($this->dataHelper->get('Article', ['Id' => $articleId], 'Id') === false) {
            $this->raiseBadRequest("Article {$articleId} doesn't exist", __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        if ($connectedUser->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $survey = $this->surveyDataHelper->getWithCreator($articleId);
        if (!$survey) {
            $this->raiseBadRequest("No survey for article {$articleId}", __FILE__, __LINE__);
            $this->redirect('/article/' . $articleId);
            return;
        }
        if ($this->authorizationDataHelper->canPersonReadSurveyResults($this->dataHelper->get('Article', ['Id' => $survey->IdArticle]), $connectedUser)) {
            $replies = $this->dataHelper->gets('Reply', ['IdSurvey' => $survey->Id]);
            $participants = [];
            $results = [];
            $options = json_decode($survey->Options);
            foreach ($options as $option) {
                $results[$option] = 0;
            }
            foreach ($replies as $reply) {
                $answers = json_decode($reply->Answers);
                $person = $this->dataHelper->get('Person', ['Id' => $reply->IdPerson], 'FirstName, LastName');
                $participants[] = [
                    'name' => $person->FirstName . ' ' . $person->LastName,
                    'answers' => $answers
                ];
                foreach ($answers as $answer) {
                    if (isset($results[$answer])) $results[$answer]++;
                }
            }

            $this->render('Article/views/survey_results.latte', [
                'survey' => $survey,
                'options' => $options,
                'results' => $results,
                'participants' => $participants,
                'articleId' => $articleId,
                'currentVersion' => Application::VERSION,
                'page' => $connectedUser->getPage(),
            ]);
        } else $this->raiseForbidden(__FILE__, __LINE__);
    }
}
