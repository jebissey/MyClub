<?php

declare(strict_types=1);

namespace app\apis;

use DateTime;
use stdClass;
use Throwable;
use app\enums\ApplicationError;
use app\enums\Period;
use app\exceptions\EmailException;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
use app\interfaces\AuthorizationServiceInterface;
use app\interfaces\EventServiceInterface;
use app\models\DataHelper;
use app\models\EventDataHelper;
use app\models\MessageDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\modules\Common\services\EmailService;
use app\helpers\PersonPreferences;
use app\valueObjects\ApiResponse;
use app\valueObjects\EmailMessage;
use app\valueObjects\EventDetailRow;
use app\valueObjects\EventParticipant;
use app\valueObjects\PersonEmailRow;

/**
 * @phpstan-import-type EventParticipantShape from EventParticipant
 */
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
        private EmailService $emailService,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function deleteEvent(int $id): void
    {
        if (!$this->eventDataHelper->eventExists($id)) {
            $this->renderJsonBadRequest("Event ({$id}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            try {
                $this->eventDataHelper->removeParticipant($id, $this->authService->getUserId());
                $apiResponse = new ApiResponse(true, ApplicationError::Ok->value);
                $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
            } catch (Throwable $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
            }
        }
    }

    public function duplicateEvent(int $id): void
    {
        if (!$this->eventDataHelper->eventExists($id)) {
            $this->renderJsonBadRequest("Event ({$id}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            try {
                $modeString = To::str(
                    WebApp::getFiltered('mode', array_column(Period::cases(), 'value'), $_GET),
                    Period::Today->value
                );
                $apiResponse = $this->eventService->duplicateEvent(
                    $id,
                    $this->application->getConnectedUser()->person->Id ?? 0,
                    Period::from($modeString)
                );
                $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode);
            } catch (Throwable $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
            }
        }
    }

    public function getEvent(int $id): void
    {
        if (!$this->eventDataHelper->eventExists($id)) {
            $this->renderJsonBadRequest("Event ({$id}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            try {
                $apiResponse = new ApiResponse(true, ApplicationError::Ok->value, [
                    'event' => $this->eventDataHelper->getEvent($id),
                    'attributes' => $this->eventDataHelper->getEventAttributes($id),
                ]);
                $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode, $apiResponse->message ?? '');
            } catch (Throwable $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
            }
        }
    }

    public function saveEvent(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            try {
                $data = $this->getJsonInput();
                $this->eventDataHelper->update($data, $this->authService->getUserId());
                $this->renderJsonOk();
            } catch (QueryException $e) {
                $this->renderJsonBadRequest($e->getMessage(), $e->getFile(), $e->getLine());
            } catch (Throwable $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
            }
        }
    }

    public function sendEmails(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            $data = $this->getJsonInput();
            $eventId = To::int($data['EventId'] ?? null);
            if ($eventId === 0) {
                $this->renderJsonError("Missing EvendId data", ApplicationError::BadRequest->value, __FILE__, __LINE__);
                return;
            }
            try {
                $event = $this->eventDataHelper->getEvent($eventId);
                $apiResponse = $this->sendEventEmails(
                    $event,
                    To::str($data['Title'] ?? null),
                    To::str($data['Body'] ?? null),
                    To::str($data['Recipients'] ?? null)
                );
                $this->renderJson($apiResponse->data, $apiResponse->success, $apiResponse->responseCode, $apiResponse->message ?? '');
            } catch (QueryException $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::BadRequest->value, $e->getFile(), $e->getLine());
            } catch (Throwable $e) {
                $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
            }
        }
    }

    #region Private functions
    private function sendEventEmails(EventDetailRow $event, string $title, string $body, string $recipients): ApiResponse
    {
        if ($recipients === 'registered') {
            /** @var list<EventParticipantShape> $rows */
            $rows = $this->participantDataHelper->getEventParticipants($event->Id);
            $participants = $this->toEmailParticipants($rows);
        } elseif ($recipients === 'unregistered') {
            $participants = []; //TODO
        } elseif ($recipients === 'all') {
            /** @var list<object{PersonId: int|string, Email: string|null}> $rows */
            $rows = $this->personDataHelper->getInterestedPeople(
                $this->eventDataHelper->getEventGroup($event->Id),
                $event->IdEventType,
                (new DateTime($event->StartTime))->format('N') - 1,
                $this->personPreferences->getPeriodOfDay($event->StartTime)
            );
            $participants = $this->toEmailParticipants($rows);
        } else {
            return new ApiResponse(false, ApplicationError::BadRequest->value, [], "Invalid recipients ($recipients)");
        }

        if ($participants) {
            $root = Application::$root;
            $eventLink = $root . '/event/' . $event->Id;
            $unsubscribeLink = $root . '/user/preferences';

            /** @var object{Email: string|null}|false $eventCreatorRow */
            $eventCreatorRow = $this->dataHelper->get('Person', ['Id' => $event->CreatedBy], 'Email');
            $eventCreatorEmail = ($eventCreatorRow !== false)
                ? PersonEmailRow::fromStdClass($eventCreatorRow)->Email
                : null;

            if ($eventCreatorEmail === null || $eventCreatorEmail === '') {
                return new ApiResponse(
                    false,
                    ApplicationError::BadRequest->value,
                    [],
                    'Invalid Email in file ' . __FILE__ . ' at line ' . __LINE__
                );
            }
            $ccList = $this->messageDataHelper->addWebAppMessages($event->Id, $participants, $title . "\n\n" . $body);
            $link = htmlspecialchars($eventLink, ENT_QUOTES, 'UTF-8');
            $htmlBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) . "<br>" .
                '<a href="' . $link . '">' . $link . '</a><br><br>' .
                "Pour ne plus recevoir ce type de message vous pouvez mettre à jour vos préférences<br>" .
                '<a href="' . htmlspecialchars($unsubscribeLink, ENT_QUOTES, 'UTF-8') . '">Se désinscrire</a>';
            try {
                $emailMessage = new EmailMessage(
                    from: $eventCreatorEmail,
                    to: $eventCreatorEmail,
                    subject: $title,
                    body: $htmlBody,
                    isHtml: true,
                    cc: $ccList
                );
                $result = $this->emailService->send($emailMessage);

                return new ApiResponse($result, $result ? ApplicationError::Ok->value : ApplicationError::Error->value);
            } catch (EmailException $e) {
                return new ApiResponse(
                    false,
                    ApplicationError::BadRequest->value,
                    [],
                    "Error {$e->getCode()} in {$e->getFile()} at {$e->getLine()}: {$e->getMessage()}"
                );
            }
        }
        return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'No participant');
    }

    /**
     * @param list<EventParticipantShape> $rows
     * @return list<EventParticipant>
     */
    private function toEmailParticipants(array $rows): array
    {
        $participants = [];
        foreach ($rows as $row) {
            $participant = EventParticipant::fromStdClass($row);
            if ($participant !== null) {
                $participants[] = $participant;
            }
        }
        return $participants;
    }
    #endregion
}
