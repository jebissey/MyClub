<?php

namespace app\controllers;

class SurveyController extends BaseController
{
    public function add($articleId)
    {
        if ($this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $article = $this->fluent->from('Article')
                    ->where('Id', $articleId)
                    ->fetch();
                if (!$article) {
                    $this->flight->redirect('/articles');
                }
                $survey = $this->fluent->from('Survey')
                    ->where('IdArticle', $article->Id)
                    ->fetch();

                $this->render('app/views/survey/add.latte', $this->params->getAll([
                    'article' => $article,
                    'survey' => $survey
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function createOrUpdate()
    {
        if ($this->getPerson(['Redactor'])) {
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
                $survey = $this->fluent->from('Survey')->where('IdArticle', $articleId)->fetch();
                if ($survey) {
                    $this->fluent->update('Survey')
                        ->set(['Question' => $question])
                        ->set(['Options' => $optionsJson])
                        ->set(['ClosingDate' => $closingDate])
                        ->set(['Visibility' => $visibility])
                        ->where('Id', $survey->Id)
                        ->execute();
                } else {

                    $this->fluent->insertInto('Survey')
                        ->values([
                            'Question' => $question,
                            'Options' => $options,
                            'IdArticle' => $articleId,
                            'ClosingDate' => $closingDate
                        ])
                        ->execute();
                }

                $this->flight->redirect('/articles/' . $articleId);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function viewResults($articleId)
    {
        if ($person = $this->getPerson([])) {
            $survey = $this->fluent->from('Survey')->join('Article ON Survey.IdArticle = Article.Id')->where('IdArticle', $articleId)->select('Article.CreatedBy')->fetch();
            if (!$survey) {
                $this->flight->redirect('/articles/' . $articleId);
            }
            if ($this->authorizations->canPersonReadSurveyResults($this->fluent->from('Article')->where('Id', $survey->IdArticle)->fetch(), $person)) {
                $replies = $this->fluent->from('Reply')->where('IdSurvey', $survey->Id)->fetchAll();

                $participants = [];
                $results = [];
                $options = json_decode($survey->Options);
                foreach ($options as $option) {
                    $results[$option] = 0;
                }
                foreach ($replies as $reply) {
                    $answers = json_decode($reply->Answers);
                    $person = $this->fluent->from('Person')->where('Id', $reply->IdPerson)->fetch();
                    $participants[] = [
                        'name' => $person->FirstName . ' ' . $person->LastName,
                        'answers' => $answers
                    ];
                    foreach ($answers as $answer) {
                        if (isset($results[$answer])) {
                            $results[$answer]++;
                        }
                    }
                }

                $this->render('app/views/survey/results.latte', [
                    'survey' => $survey,
                    'options' => $options,
                    'results' => $results,
                    'participants' => $participants,
                    'articleId' => $articleId,
                    'currentVersion' => self::VERSION
                ]);
            } else {
                $this->application->error403(__FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
