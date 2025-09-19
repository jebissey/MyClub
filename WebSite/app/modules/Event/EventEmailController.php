<?php

declare(strict_types=1);

namespace app\modules\Event;

use app\enums\FilterInputRule;
use app\enums\WeekdayFormat;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\modules\Common\AbstractController;

class EventEmailController extends AbstractController
{
    public function __construct(
        Application $application,
        private PersonDataHelper $personDataHelper,
    ) {
        parent::__construct($application);
    }

    public function fetchEmails(): void
    {
        if (!($this->application->getConnectedUser()->isEventManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Event/views/getEmails.latte', Params::getAll([
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'eventTypes' => $this->dataHelper->gets('EventType', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'weekdayNames' => TranslationManager::getWeekdayNames(),
            'timeOptions' => $this->getAllLabels(),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function copyEmails(): void
    {
        if (!($this->application->getConnectedUser()->isEventManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
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
        $filteredEmails = $this->personDataHelper->getEmailsOfInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
        $groupName = $idGroup != null ? $this->dataHelper->get('Group', ['Id' => $idGroup], 'Name')->Name ?? '' : '';
        $eventTypeName = $idEventType != null ? $this->dataHelper->get('EventType', ['Id' => $idEventType], 'Name') : '';
        $dayOfWeekName = $dayOfWeek != null ? TranslationManager::getWeekdayNames()[$dayOfWeek] : '';

        $this->render('Event/views/copyToClipBoard.latte', Params::getAll([
            'emailsJson' => json_encode($filteredEmails),
            'emails' => $filteredEmails,
            'filters' => "$groupName / $eventTypeName / $dayOfWeekName / $timeOfDay",
            'people' => $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Email, Phone, FirstName, LastName, NickName', '', true),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }
}
