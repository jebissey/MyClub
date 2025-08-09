<?php

namespace app\controllers;

use RuntimeException;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\enums\WeekdayFormat;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\services\EmailService;
use app\models\PersonDataHelper;

class EmailController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function fetchEmails()
    {
        if ($this->connectedUser->get()->isEventManager() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'dayOfWeek' => $this->application->enumToValues(WeekdayFormat::class),
                    'timeOfDay' => FilterInputRule::HtmlSafeName->value,
                    'idGroup' => FilterInputRule::Int->value,
                    'idEventType' => FilterInputRule::Int->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $idGroup = $input['idGroup'];
                $idEventType =  $input['idEventType'];
                $dayOfWeek = $input['dayOfWeek'] ?? '';
                $timeOfDay = $input['timeOfDay'] ?? '';
                $filteredEmails = (new PersonDataHelper($this->application))->getEmailsOfInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
                $groupName = $idGroup != null ? $this->dataHelper->get('Group', ['Id' => $idGroup], 'Name')->Name ?? '' : '';
                $eventTypeName = $idEventType != null ? $this->dataHelper->get('EventType', ['Id', $idEventType], 'Name') : '';
                $dayOfWeekName = $dayOfWeek != null ? TranslationManager::getWeekdayNames()[$dayOfWeek] : '';

                $this->render('app/views/emails/copyToClipBoard.latte', Params::getAll([
                    'emailsJson' => json_encode($filteredEmails),
                    'emails' => $filteredEmails,
                    'filters' => "$groupName / $eventTypeName / $dayOfWeekName / $timeOfDay",
                    'people' => $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Email, Phone, FirstName, LastName, NickName', '', true),
                ]));
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/emails/getEmails.latte', Params::getAll([
                    'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                    'eventTypes' => $this->dataHelper->gets('EventType', ['Inactivated' => 0], 'Id, Name', 'Name'),
                    'weekdayNames' => TranslationManager::getWeekdayNames(),
                    'timeOptions' => $this->getAllLabels(),
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function fetchEmailsForArticle($idArticle)
    {
        if ($this->connectedUser->get()->isRedactor() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $article = $this->dataHelper->get('Article', ['Id', $idArticle], 'CreatedBy');
                if (!$article) throw new RuntimeException('Fatal program error in file ' + __FILE__ + ' at line ' + __LINE__);
                $articleCreatorEmail = $this->dataHelper->get('Person', ['Id', $article->CreatedBy], 'Email')->Email;
                if (!$articleCreatorEmail) {
                    $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown author of article '$idArticle' in file " . __FILE__ . ' at line ' . __LINE__);
                    return;
                }
                $filteredEmails = (new PersonDataHelper($this->application))->getPersonWantedToBeAlerted($idArticle);
                $root = Application::$root;
                $articleLink = $root . '/articles/' . $idArticle;
                $unsubscribeLink = $root . '/user/preferences';
                $emailTitle = 'BNW - Un nouvel article est disponible';
                $message = "Conformément à vos souhaits, ce message vous signale la présence d'un nouvel article" . "\n\n" . $articleLink
                    . "\n\n Pour ne plus recevoir ce type de message vous pouvez mettre à jour vos préférences" . $unsubscribeLink;
                EmailService::send(
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
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
