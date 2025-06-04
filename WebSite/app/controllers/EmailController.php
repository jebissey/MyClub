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
                $filteredEmails = $this->email->getEmailsOfInterestedPeople($idGroup, $dayOfWeek, $timeOfDay);
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
        if ($this->getPerson(['Redactor'])) {
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
                    }
                }
                $this->render('app/views/emails/copyToClipBoard.latte', $this->params->getAll([
                    'emailsJson' => json_encode($filteredEmails),
                    'emails' => $filteredEmails,
                    'filters' => "subcription to new article",
                    'phones' => $this->fluent->from('Person')->where('Inactivated', 0)->fetchAll('Email', 'Phone'),
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
