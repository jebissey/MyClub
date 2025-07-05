<?php

namespace app\controllers;

use Flight\Engine;
use PDO;
use app\helpers\Email;

class EmailController extends BaseController
{
    private $email;
    private $personPreferences;

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
        if ($this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $article = $this->fluent->from('Article')->where('Id', $idArticle)->fetch();
                if (!$article) die('Fatal program error in file ' + __FILE__ + ' at line ' + __LINE__);
                $articleCreatorEmail = $this->fluent->from('Person')->where('Id', $article->CreatedBy)->fetch('Email');
                if (!$articleCreatorEmail) {
                    $this->application->error471('Invalid Email', __FILE__, __LINE__);
                    return;
                }
                $filteredEmails = $this->personPreferences->getPersonWantedToBeAlerted($idArticle);
                $root = 'https://' . $_SERVER['HTTP_HOST'];
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
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function getAlreadySentMessages($filter)
    {
        $sql = "
            SELECT *, 
                (strftime('%s', LastUpdate) / 20) AS TimeGroup, 
                COUNT(*) AS Count
            FROM Message
            WHERE 'From' = 'Webapp' and Text like '%$filter%'
            GROUP BY TimeGroup, Text";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
