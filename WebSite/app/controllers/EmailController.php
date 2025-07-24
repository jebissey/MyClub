<?php

namespace app\controllers;

use Flight\Engine;
use app\helpers\Application;
use app\helpers\Email;
use app\helpers\PersonPreferences;

class EmailController extends BaseController
{
    private Email $email;
    private PersonPreferences $personPreferences;

    public function __construct(Engine $flight)
    {
        parent::__construct($flight);
        $this->email = new Email();
        $this->personPreferences = new PersonPreferences();
    }

    public function fetchEmails()
    {
        if ($this->personDataHelper->getPerson(['EventManager'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $idGroup = $_POST['idGroup'] ?? '';
                $idEventType = $_POST['idEventType'] ?? '';
                $dayOfWeek = $_POST['dayOfWeek'] ?? '';
                $timeOfDay = $_POST['timeOfDay'] ?? '';
                $filteredEmails = $this->email->getEmailsOfInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
                $groupName = $idGroup != '' ? $this->dataHelper->get('Group', ['Id' => $idGroup], 'Name')->Name ?? '' : '';
                $eventTypeName = $idEventType != '' ? $this->dataHelper->get('EventType', ['Id', $idEventType], 'Name') : '';
                $dayOfWeekName = $dayOfWeek != '' ? ['Lu.', 'Ma.', 'Me.', 'Je.', 'Ve.', 'Sa.', 'Di.', ''][$dayOfWeek] : '';
                
                $this->render('app/views/emails/copyToClipBoard.latte', $this->params->getAll([
                    'emailsJson' => json_encode($filteredEmails),
                    'emails' => $filteredEmails,
                    'filters' => "$groupName / $eventTypeName / $dayOfWeekName / $timeOfDay",
                    'phones' => $this->dataHelper->gets('Person', ['Inactivated' => 0], "Email', 'Phone'"),
                ]));
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/emails/getEmails.latte', $this->params->getAll([
                    'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                    'eventTypes' => $this->dataHelper->gets('EventType', ['Inactivated' => 0], 'Id, Name', 'Name'),
                ]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function fetchEmailsForArticle($idArticle)
    {
        if ($this->personDataHelper->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $article = $this->dataHelper->get('Article', ['Id', $idArticle]);
                if (!$article) die('Fatal program error in file ' + __FILE__ + ' at line ' + __LINE__);
                $articleCreatorEmail = $this->dataHelper->get('Person', ['Id', $article->CreatedBy])->Email;
                if (!$articleCreatorEmail) {
                    $this->application->error471('Invalid Email', __FILE__, __LINE__);
                    return;
                }
                $filteredEmails = $this->personPreferences->getPersonWantedToBeAlerted($idArticle);
                $root = Application::$root;
                $articleLink = $root . '/articles/' . $idArticle;
                $unsubscribeLink = $root . '/user/preferences';
                $emailTitle = 'BNW - Un nouvel article est disponible';
                $message = "Conformément à vos souhaits, ce message vous signale la présence d'un nouvel article" . "\n\n" . $articleLink
                    . "\n\n Pour ne plus recevoir ce type de message vous pouvez mettre à jour vos préférences" . $unsubscribeLink;
                Email::send(
                    $articleCreatorEmail,
                    $articleCreatorEmail,
                    $emailTitle,
                    $message,
                    null,
                    $filteredEmails,
                    false
                );
                $_SESSION['success'] = "Un courriel a été envoyé aux abonnés";
                $this->flight->redirect('/articles/' . $idArticle);
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }
}
