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

class EventService implements EventServiceInterface
{
    private DataHelper $dataHelper;
    private EventDataHelper $eventDataHelper;
    private MessageDataHelper $messageDataHelper;
    private ParticipantDataHelper $participantDataHelper;
    private PersonDataHelper $personDataHelper;
    private PersonPreferences $personPreferences;

    public function __construct(
        DataHelper $dataHelper,
        EventDataHelper $eventDataHelper,
        MessageDataHelper $messageDataHelper,
        ParticipantDataHelper $participantDataHelper,
        PersonDataHelper $personDataHelper,
        PersonPreferences $personPreferences
    ) {
        $this->dataHelper = $dataHelper;
        $this->eventDataHelper = $eventDataHelper;
        $this->messageDataHelper = $messageDataHelper;
        $this->participantDataHelper = $participantDataHelper;
        $this->personDataHelper = $personDataHelper;
        $this->personPreferences = $personPreferences;
    }

    public function deleteEvent(int $id, int $userId): array
    {
        return $this->eventDataHelper->delete_($id, $userId);
    }

    public function duplicateEvent(int $id, int $userId, string $mode): array
    {
        return $this->eventDataHelper->duplicate($id, $userId, $mode);
    }

    public function getEvent(int $id): array
    {
        return [
            'success' => true,
            'event' => $this->eventDataHelper->getEvent($id),
            'attributes' => $this->eventDataHelper->getEventAttributes($id),
        ];
    }

    public function sendEventEmails(object $event, string $title, string $body, string $recipients): array
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
        } else return [['success' => false, 'message' => "Invalid recipients ($recipients)"], ApplicationError::BadRequest->value];
        if ($participants) {
            $root = Application::$root;
            $eventLink = $root . '/event/' . $event->Id;
            $unsubscribeLink = $root . '/user/preferences';
            $eventCreatorEmail = $this->dataHelper->get('Person', ['Id' => $event->CreatedBy], 'Email')->Email;
            if (!$eventCreatorEmail) {
                return [['success' => false, 'message' => 'Invalid Email in file ' + __FILE__ + ' at line ' + __LINE__], ApplicationError::BadRequest->value];
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
            return [['success' => $result], $result ? ApplicationError::Ok->value : ApplicationError::Error->value];
        }
        return [['success' => false, 'message' => 'No participant'], ApplicationError::BadRequest->value];
    }
}
