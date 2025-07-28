<?php

namespace app\controllers;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Email;
use app\helpers\PersonDataHelper;

class EmailController extends BaseController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function fetchEmails()
    {
        if ($this->personDataHelper->getPerson(['EventManager'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $idGroup = $_POST['idGroup'] ?? '';
                $idEventType = $_POST['idEventType'] ?? '';
                $dayOfWeek = $_POST['dayOfWeek'] ?? '';
                $timeOfDay = $_POST['timeOfDay'] ?? '';
                $filteredEmails = (new PersonDataHelper($this->application))->getEmailsOfInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
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
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function fetchEmailsForArticle($idArticle)
    {
        if ($this->personDataHelper->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $article = $this->dataHelper->get('Article', ['Id', $idArticle]);
                if (!$article) die('Fatal program error in file ' + __FILE__ + ' at line ' + __LINE__);
                $articleCreatorEmail = $this->dataHelper->get('Person', ['Id', $article->CreatedBy])->Email;
                if (!$articleCreatorEmail) {
                    $this->application->getErrorManager()->raise(ApplicationError::InvalidParameter, "Unknown author of article '$idArticle' in file " . __FILE__ . ' at line ' . __LINE__);
                    return;
                }
                $filteredEmails = $this->personDataHelper->getPersonWantedToBeAlerted($idArticle);
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
            } else $this->application->getErrorManager()->raise(ApplicationError::InvalidRequestMethod, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::NotAllowed, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
