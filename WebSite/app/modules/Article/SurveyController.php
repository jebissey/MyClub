<?php

namespace app\modules\Article;

use RuntimeException;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\enums\SurveyVisibility;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\SurveyDataHelper;
use app\modules\Common\AbstractController;

class SurveyController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function add($articleId)
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $article = $this->dataHelper->get('Article', ['Id' => $articleId], 'Title, Id, ');
                if (!$article) {
                    $this->flight->redirect('/articles');
                    return;
                }

                $this->render('Article/views/survey_add.latte', Params::getAll([
                    'article' => $article,
                    'survey' => $this->dataHelper->get('Survey', ['IdArticle' => $article->Id], 'Question, Options, ClosingDate, Visibility')
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function createOrUpdate()
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'article_id' => FilterInputRule::Int->value,
                    'question' => FilterInputRule::HtmlSafeText->value,
                    'closingDate' => FilterInputRule::DateTime->value,
                    'visibility' => $this->application->enumToValues(SurveyVisibility::class),
                    'options' => FilterInputRule::ArrayString,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $articleId = $input['article_id'] ?? throw new RuntimeException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
                $question = $input['question'] ?? '???';
                $closingDate = $input['closingDate'] ?? date('now', '+7 days');
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
                $this->flight->redirect('/articles/' . $articleId);
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function viewResults($articleId)
    {
        $connectedUser = $this->connectedUser->get();
        if ($connectedUser->person != null) {
            $survey = (new SurveyDataHelper($this->application))->getWithCreator($articleId);
            if (!$survey) {
                $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "No survey for article {$articleId} in file " . __FILE__ . ' at line ' . __LINE__);
                $this->flight->redirect('/articles/' . $articleId);
                return;
            }
            if ((new AuthorizationDataHelper($this->application))->canPersonReadSurveyResults($this->dataHelper->get('Article', ['Id' => $survey->IdArticle]), $connectedUser)) {
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
                    'currentVersion' => Application::VERSION
                ]);
            } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Unauthorized, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
