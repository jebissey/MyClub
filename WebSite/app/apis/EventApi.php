<?php

namespace app\apis;

use DateTime;
use Throwable;

use app\enums\ApplicationError;
use app\enums\EventAudience;
use app\enums\Period;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
use app\interfaces\AuthorizationServiceInterface;
use app\interfaces\EventServiceInterface;
use app\models\DataHelper;
use app\models\EventDataHelper;
use app\models\MessageDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\helpers\PersonPreferences;
use app\services\EmailService;
use app\valueObjects\ApiResponse;

class EventApi extends AbstractApi
{
    public function __construct(
        Application $application,
        private AuthorizationServiceInterface $authService,
        private EventDataHelper $eventDataHelper,
        private EventServiceInterface $eventService,
        private ParticipantDataHelper $participantDataHelper,
        private PersonPreferences $personPreferences,
        private MessageDataHelper $messageDataHelper,
        ConnectedUser $connectedUser, 
        DataHelper $dataHelper, 
        PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function deleteEvent(int $id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->eventDataHelper->eventExists($id)) {
            $this->renderJsonBadRequest("Event ({$id}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        try {
            $this->eventDataHelper->delete_($id, $this->authService->getUserId());
            $apiResponse = new ApiResponse(true, ApplicationError::Ok->value);
            $this->renderJson([$apiResponse->data], $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function duplicateEvent($id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->eventDataHelper->eventExists($id)) {
            $this->renderJsonBadRequest("Event ({$id}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        try {
            [$success, $response, $statusCode] = $this->eventService->duplicateEvent(
                $id,
                $this->application->getConnectedUser()->person->Id,
                WebApp::getFiltered('mode', $this->application->enumToValues(Period::class), $_GET) ?: Period::Today->value
            );
            $this->renderJson($response, $success, $statusCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function getEvent($id): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->eventDataHelper->eventExists($id)) {
            $this->renderJsonBadRequest("Event ({$id}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        try {
            $apiResponse = new ApiResponse(true, ApplicationError::Ok->value, [
                'event' => $this->eventDataHelper->getEvent($id),
                'attributes' => $this->eventDataHelper->getEventAttributes($id),
            ]);
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function saveEvent(): void
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
            $apiResponse = $this->update($data, $this->authService->getUserId());
            $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    public function sendEmails()
    {
        if (!$this->authService->isEventManager()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = $this->getJsonInput();
        $eventId = $data['EventId'] ?? 0;
        if ($eventId === 0) {
            $this->renderJsonError("Missing EvendId data", ApplicationError::BadRequest->value);
            return;
        }
        try {
            $event = $this->eventDataHelper->getEvent($eventId);
            $apiResponse = $this->sendEventEmails($event, $data['Title'] ?? '', $data['Body'] ?? '', $data['Recipients'] ?? '');
            $this->renderJson([$apiResponse->data], $apiResponse->success,  $apiResponse->responseCode);
        } catch (QueryException $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::BadRequest->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value);
        }
    }

    #region Private functions
    private function sendEventEmails(object $event, string $title, string $body, string $recipients): ApiResponse
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

    private function update(array $data, int $personId): ApiResponse
    {
        $values = [
            'Summary'         => $data['summary'] ?? '',
            'Description'     => $data['description'] ?? '',
            'Location'        => $data['location'] ?? '',
            'StartTime'       => $data['startTime'] ?? date('Y-m-d H:i:s'),
            'Duration'        => $data['duration'] ?? 1,
            'IdEventType'     => $data['idEventType'] ?? 0,
            'CreatedBy'       => $personId,
            'MaxParticipants' => $data['maxParticipants'] ?? 0,
            'Audience'        => $data['audience'] ?? EventAudience::ForClubMembersOnly->value,
            'LastUpdate'      => date('Y-m-d H:i:s'),
        ];
        $this->dataHelper->beginTransaction();
        try {
            if ($data['formMode'] == 'create') {
                $eventId = $this->dataHelper->set('Event', $values);
            } elseif ($data['formMode'] == 'update') {
                $eventId = $data['id'];
                if (!$this->dataHelper->get('Event', ['Id' => $eventId], 'Id')) throw new QueryException("Event {$eventId} doesn't exist");
                $this->dataHelper->set('Event', $values, ['Id' => $data['id']]);
                $this->dataHelper->delete('EventAttribute', ['IdEvent' => $eventId]);
                $this->dataHelper->delete('EventNeed', ['IdEvent' => $eventId]);
            } else Application::unreachable($data['formMode'], __FILE__, __LINE__);
            $this->insertEventAttributes($eventId, $data['attributes'] ?? []);
            $this->insertEventNeeds($eventId, $data['needs'] ?? []);
            $this->dataHelper->commitTransaction();
            return new ApiResponse(true, ApplicationError::Ok->value, ['eventId' => $eventId]);
        } catch (QueryException $e) {
            $this->dataHelper->rollBackTransaction();
            return new ApiResponse(false, ApplicationError::BadRequest->value, [
                'message' => 'Erreur lors de l\'écriture dans la base de données',
                'error'   => $e->getMessage()
            ]);
        } catch (Throwable $e) {
            $this->dataHelper->rollBackTransaction();
            return new ApiResponse(false, ApplicationError::Error->value, [
                'message' => 'Erreur lors de l\'écriture dans la base de données',
                'error'   => $e->getMessage()
            ]);
        }
    }

    private function insertEventAttributes(int $eventId, array $attributes): void
    {
        if (!empty($attributes)) {
            foreach ($attributes as $attributeId) {
                $this->dataHelper->set('EventAttribute', [
                    'IdEvent'     => $eventId,
                    'IdAttribute' => $attributeId
                ]);
            }
        }
    }

    private function insertEventNeeds(int $eventId, array $needs): void
    {
        if (!empty($needs)) {
            foreach ($needs as $need) {
                $this->dataHelper->set('EventNeed', [
                    'IdEvent' => $eventId,
                    'IdNeed'  => $need['id'],
                    'Counter' => $need['counter'],
                ]);
            }
        }
    }
}
