<?php

namespace app\controllers;

use Flight\Engine;
use PDO;
use app\helpers\Email;

class EmailController extends BaseController
{
    private $email;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->email = new Email($this->pdo);
    }

    public function fetchEmails()
    {
        if ($this->getPerson(['EventManager'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $idGroup = $_POST['idGroup'] ?? '';
                $idEventType = $_POST['idEventType'] ?? '';
                $dayOfWeek = $_POST['dayOfWeek'] ?? '';
                $timeOfDay = $_POST['timeOfDay'] ?? '';
                $filteredEmails = $this->email->getEmailsOfInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
                $groupName = $idGroup != '' ? $this->getGroup($idGroup)->Name : '';
                $eventTypeName = $idEventType != '' ? $this->fluent->from('EventType')->where('Id', $idEventType)->fetch('Name') : '';
                $dayOfWeekName = $dayOfWeek != '' ? ['Lu.', 'Ma.', 'Me.', 'Je.', 'Ve.', 'Sa.', 'Di.', ''][$dayOfWeek] : '';
                $this->render('app/views/emails/copyToClipBoard.latte', $this->params->getAll([
                    'emailsJson' => json_encode($filteredEmails),
                    'emails' => $filteredEmails,
                    'filters' => "$groupName / $eventTypeName / $dayOfWeekName / $timeOfDay",
                    'phones' => $this->fluent->from('Person')->where('Inactivated', 0)->fetchAll('Email', 'Phone'),
                ]));
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {

                $this->render('app/views/emails/getEmails.latte', $this->params->getAll([
                    'groups' => $this->getGroups(),
                    'eventTypes' => $this->fluent->from('EventType')->where('Inactivated', 0)->orderBy('Name')->fetchAll('Id', 'Name'),
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function fetchEmailsForArticle($idArticle)
    {
        if ($person = $this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $idGroup = $this->fluent->from('Article')->where('Id', $idArticle)->fetch('IdGroup');
                $idSurvey = $this->fluent->from('Survey')->where('IdArticle', $idArticle)->fetch('Id');

                $persons = $this->email->getPersons($idGroup);
                $filteredEmails = [];
                foreach ($persons as $person) {
                    $include = false;
                    if ($person->Preferences ?? '' != '') {
                        $preferences = json_decode($person->Preferences ?? '', true);
                        if ($preferences != '' && isset($preferences['eventTypes']['newArticle'])) {
                            if (isset($preferences['eventTypes']['newArticle']['pollOnly'])) {
                                if ($idSurvey) {
                                    $include = true;
                                }
                            } else {
                                $include = true;
                            }
                        }
                    }
                    if ($include) {
                        $filteredEmails[] = $person->Email;
                        $this->fluent->insertInto('Message')
                            ->values([
                                'EventId' => null,
                                'PersonId' => $person->Id,
                                'Text' =>  "New article \n\n /articles/" . $idArticle,
                                '"From"' => 'Webapp'
                            ])
                            ->execute();
                    }
                }
                $article = $this->fluent->from('Article')->where('Id', $idArticle)->fetch();
                if (!$article) die('Fatal program error in file ' + __FILE__ + ' at line ' + __LINE__);
                $articleCreatorEmail = $this->fluent->from('Person')->where('Id', $article->CreatedBy)->fetch('Email');
                if (!$articleCreatorEmail) {
                    $this->renderJson(['success' => false, 'message' => 'Invalid Email in file ' + __FILE__ + ' at line ' + __LINE__], 404);
                     $this->application->error471('Invalid Email', __FILE__, __LINE__);
                    return;
                }
                $emailTitle = 'BNW - Un nouvel article est disponible';
                $message = "Conformement à vos souhaits, ce message vous signale la présence d'un nouvel article";
                Email::send(
                    $articleCreatorEmail,
                    $articleCreatorEmail,
                    $emailTitle,
                    $message . "\n\n" . 'https://' . $_SERVER['HTTP_HOST'] . '/articles/' . $idArticle,
                    null,
                    $filteredEmails,
                    false
                );
                $_SESSION['success'] = "Un courriel a été envoyé aux abonnés";
                $this->flight->redirect('/articles/' . $idArticle);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
