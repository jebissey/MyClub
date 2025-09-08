<?php

namespace app\services;

use DateTime;

use app\enums\ApplicationError;
use app\interfaces\EventServiceInterface;
use app\helpers\Application;
use app\helpers\PersonPreferences;
use app\models\DataHelper;
use app\models\EventDataHelper;
use app\models\MessageDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\valueObjects\ApiResponse;

class EventService implements EventServiceInterface
{
    public function __construct(
        private DataHelper $dataHelper,
        private EventDataHelper $eventDataHelper,
        private MessageDataHelper $messageDataHelper,
        private ParticipantDataHelper $participantDataHelper,
        private PersonPreferences $personPreferences,
        private PersonDataHelper $personDataHelper
    ) {}

    public function deleteEvent(int $id, int $userId): ApiResponse
    {
        $this->eventDataHelper->delete_($id, $userId);
        return new ApiResponse(true, ApplicationError::Ok->value);
    }

    public function duplicateEvent(int $id, int $userId, string $mode): ApiResponse
    {
        return $this->eventDataHelper->duplicate($id, $userId, $mode);
    }

    public function getEvent(int $id): ApiResponse
    {
        return new ApiResponse(true, ApplicationError::Ok->value, [
            'event' => $this->eventDataHelper->getEvent($id),
            'attributes' => $this->eventDataHelper->getEventAttributes($id),
        ]);
    }

    public function sendEventEmails(object $event, string $title, string $body, string $recipients): ApiResponse
    {
        if ($recipients === 'registered') $participants = $this->participantDataHelper->getEventParticipants($event->Id);
        else if ($recipients === 'unregistered') {
            //TODO
        } else if ($recipients === 'all') {
            $participants = $this->personDataHelper->getInterestedPeople(
                $this->eventDataHelper->getEventGroup($event->Id),
                $event->IdEventType ?? null,
                (new DateTime($event->StartTime))->format('N') - 1,
                $this->personPreferences->getPeriodOfDay($event->StartTime)
            );
        } else return new ApiResponse(false, ApplicationError::BadRequest->value, [], "Invalid recipients ($recipients)");
        if ($participants) {
            $root = Application::$root;
            $eventLink = $root . '/event/' . $event->Id;
            $unsubscribeLink = $root . '/user/preferences';
            $eventCreatorEmail = $this->dataHelper->get('Person', ['Id' => $event->CreatedBy], 'Email')->Email;
            if (!$eventCreatorEmail) {
                return new ApiResponse(false, ApplicationError::BadRequest->value, [],  'Invalid Email in file ' + __FILE__ + ' at line ' + __LINE__);
            }
            $ccList = $this->messageDataHelper->addWebAppMessages($event->Id, $participants, $title . "\n\n" . $body);
            $result = EmailService::send(
                $eventCreatorEmail,
                $eventCreatorEmail,
                $title,
                $body . "\n" . $eventLink . "\n\n Pour ne plus recevoir ce type de message vous pouvez mettre à jour vos préférences\n" . $unsubscribeLink,
                $ccList,
                null,
                false
            );
            return new ApiResponse($result, $result ? ApplicationError::Ok->value : ApplicationError::Error->value);
        }
        return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'No participant');
    }
}
