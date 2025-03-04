<?php

namespace app\controllers;

use Exception;

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

                $this->latte->render('app/views/survey/add.latte', $this->params->getAll([
                    'article' => $article
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function create()
    {
        if ($this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $articleId = $_POST['article_id'] ?? null;
                $question = $_POST['question'] ?? '';
                $options = isset($_POST['options']) ? json_encode($_POST['options']) : '[]';

                $this->fluent->insertInto('Survey')
                    ->values([
                        'Question' => $question,
                        'Options' => $options,
                        'IdArticle' => $articleId
                    ])
                    ->execute();

                $this->flight->redirect('/articles/' . $articleId);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showReplyForm($articleId)
    {
        if ($person = $this->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $survey = $this->fluent->from('Survey')
                    ->where('IdArticle', $articleId)
                    ->fetch();

                if (!$survey) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => "Aucun sondage trouvé pour l'article $articleId"]);
                    exit;
                }

                try {
                    $options = json_decode($survey['Options']);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception("Erreur de décodage JSON : " . json_last_error_msg());
                    }

                    $previousReply = $this->fluent->from('Reply')
                        ->where('IdSurvey', $survey['Id'])
                        ->where('IdPerson', $person['Id'])
                        ->fetch();

                    $previousAnswers = $previousReply ? json_decode($previousReply['Answers'], true) : null;

                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'survey' => [
                            'id' => $survey['Id'],
                            'question' => $survey['Question'],
                            'options' => $options,
                            'previousAnswers' => $previousAnswers
                        ]
                    ]);
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                exit;
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function saveReply()
    {
        if ($person = $this->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                $surveyId = $data['survey_id'] ?? null;
                if (!$surveyId) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                    exit;
                }
                $answers = isset($data['survey_answers']) ? json_encode($data['survey_answers']) : '[]';

                $existingReply = $this->fluent->from('Reply')
                    ->where('IdPerson', $person['Id'])
                    ->where('IdSurvey', $surveyId)
                    ->fetch();
                if ($existingReply) {
                    $this->fluent->update('Reply')
                        ->set(['Answers' => $answers])
                        ->where('Id', $existingReply['Id'])
                        ->execute();
                } else {
                    $this->fluent->insertInto('Reply')
                        ->values([
                            'IdPerson' => $person['Id'],
                            'IdSurvey' => $surveyId,
                            'Answers' => $answers
                        ])
                        ->execute();
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } else {
                http_response_code(470);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Bad request method']);
            }
        } else {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        }
    }

    public function viewResults($articleId)
    {
        $survey = $this->fluent->from('Survey')
            ->where('IdArticle', $articleId)
            ->fetch();

        if (!$survey) {
            $this->flight->redirect('/articles/' . $articleId);
        }

        $replies = $this->fluent->from('Reply')
            ->where('IdSurvey', $survey['Id'])
            ->fetchAll();

        $participants = [];
        $results = [];
        $options = json_decode($survey['Options']);

        foreach ($options as $option) {
            $results[$option] = 0;
        }

        foreach ($replies as $reply) {
            $answers = json_decode($reply->Answers);
            $person = $this->fluent->from('Person')
                ->where('Id', $reply->IdPerson)
                ->fetch();

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

        $this->latte->render('app/views/survey/results.latte', [
            'survey' => $survey,
            'options' => $options,
            'results' => $results,
            'participants' => $participants,
            'articleId' => $articleId
        ]);
    }
}
