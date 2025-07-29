<?php

namespace app\controllers;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\AuthorizationDataHelper;
use app\helpers\SurveyDataHelper;

class SurveyController extends BaseController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function add($articleId)
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isRedactor()) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $article = $this->dataHelper->get('Article', ['Id' => $articleId]);
                if (!$article) {
                    $this->flight->redirect('/articles');
                    return;
                }

                $this->render('app/views/survey/add.latte', $this->params->getAll([
                    'article' => $article,
                    'survey' => $this->dataHelper->get('Survey', ['IdArticle' => $article->Id])
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function createOrUpdate()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isRedactor()) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $articleId = $_POST['article_id'] ?? null;
                $question = $_POST['question'] ?? '';
                $closingDate = $_POST['closingDate'] ?? date('now', '+7 days');
                $visibility = $_POST['visibility'] ?? 'redactor';
                $options = [];
                if (isset($_POST['options']) && is_array($_POST['options'])) {
                    foreach ($_POST['options'] as $option) {
                        $options[] = str_replace('"', "''", $option);
                    }
                }
                $optionsJson = json_encode($options);
                $fields = [
                    'Question' => $question,
                    'Options' => $optionsJson,
                    'ClosingDate' => $closingDate,
                    'IdArticle' => $articleId,
                    'Visibility' => $visibility
                ];
                $survey = $this->dataHelper->get('Survey', ['IdArticle' => $articleId]);
                if ($survey) $this->dataHelper->set('Survey', $fields, ['Id' => $survey->Id]);
                else         $this->dataHelper->set('Survey', $fields);
                $this->flight->redirect('/articles/' . $articleId);
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function viewResults($articleId)
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $survey = (new SurveyDataHelper($this->application))->getWithCreator($articleId);
            if (!$survey) {
                $this->flight->redirect('/articles/' . $articleId);
                return;
            }
            if ((new AuthorizationDataHelper($this->application))->canPersonReadSurveyResults($this->dataHelper->get('Article', ['Id' => $survey->IdArticle]), $person)) {
                $replies = $this->dataHelper->gets('Reply', ['IdSurvey' => $survey->Id]);
                $participants = [];
                $results = [];
                $options = json_decode($survey->Options);
                foreach ($options as $option) {
                    $results[$option] = 0;
                }
                foreach ($replies as $reply) {
                    $answers = json_decode($reply->Answers);
                    $person = $this->dataHelper->get('Person', ['Id' => $reply->IdPerson]);
                    $participants[] = [
                        'name' => $person->FirstName . ' ' . $person->LastName,
                        'answers' => $answers
                    ];
                    foreach ($answers as $answer) {
                        if (isset($results[$answer])) $results[$answer]++;
                    }
                }

                $this->render('app/views/survey/results.latte', [
                    'survey' => $survey,
                    'options' => $options,
                    'results' => $results,
                    'participants' => $participants,
                    'articleId' => $articleId,
                    'currentVersion' => Application::getVersion()
                ]);
            } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
