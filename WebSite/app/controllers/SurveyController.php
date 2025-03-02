<?php

namespace app\controllers;

class SurveyController extends BaseController
{
    public function add($articleId)
    {
        $article = $this->fluent->from('Article')
            ->where('Id', $articleId)
            ->fetch();

        if (!$article) {
            $this->flight->redirect('/articles');
        }

        $this->latte->render('surveys/add.latte', [
            'article' => $article
        ]);
    }

    public function create()
    {
        if ($person = $this->getPerson(['Redactor'])) {
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
        $survey = $this->fluent->from('Survey')
            ->where('IdArticle', $articleId)
            ->fetch();

        if (!$survey) {
            return json_encode(['success' => false, 'message' => 'Aucun sondage trouvé']);
        }

        $options = json_decode($survey->Options);

        return json_encode([
            'success' => true,
            'survey' => [
                'id' => $survey->Id,
                'question' => $survey->Question,
                'options' => $options
            ]
        ]);
    }

    public function saveReply()
    {
        if ($person = $this->getPerson([])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);

                $surveyId = $data['survey_id'] ?? null;
                $userEmail = $data['user_email'] ?? '';
                $answers = isset($data['answers']) ? json_encode($data['answers']) : '[]';

                if (!$surveyId || empty($userEmail)) {
                    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                    return;
                }

                if (!$person) {
                    return json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
                }

                // Vérifier si l'utilisateur a déjà répondu
                $existingReply = $this->fluent->from('Reply')
                    ->where('IdPerson', $person->Id)
                    ->where('IdSurvey', $surveyId)
                    ->fetch();

                if ($existingReply) {
                    // Mettre à jour la réponse existante
                    $this->fluent->update('Reply')
                        ->set(['Answers' => $answers])
                        ->where('Id', $existingReply->Id)
                        ->execute();
                } else {
                    // Créer une nouvelle réponse
                    $this->fluent->insertInto('Reply')
                        ->values([
                            'IdPerson' => $person->Id,
                            'IdSurvey' => $surveyId,
                            'Answers' => $answers
                        ])
                        ->execute();
                }

                return json_encode(['success' => true]);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
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
            ->where('IdSurvey', $survey->Id)
            ->fetchAll();

        $participants = [];
        $results = [];
        $options = json_decode($survey->Options);

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

        $this->latte->render('surveys/results.latte', [
            'survey' => $survey,
            'options' => $options,
            'results' => $results,
            'participants' => $participants,
            'articleId' => $articleId
        ]);
    }
}
