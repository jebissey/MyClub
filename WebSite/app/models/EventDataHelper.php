<?php

declare(strict_types=1);

namespace app\models;

use DateTime;
use DateTimeImmutable;
use PDO;
use stdClass;
use Throwable;
use app\enums\ApplicationError;
use app\enums\EventAudience;
use app\enums\Period;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\To;
use app\modules\Common\valueObjects\ApiResponse;
use app\modules\Event\valueObjects\EventAudienceRow;
use app\modules\Common\valueObjects\EventChatRow;
use app\modules\Event\valueObjects\EventDetailRow;
use app\modules\Event\valueObjects\EventExternalRow;
use app\modules\Event\valueObjects\EventFullRow;
use app\modules\Event\valueObjects\RegistrationEventInfo;

/**
 * @phpstan-import-type EventExternalRowShape from EventExternalRow
 */
final class EventDataHelper extends Data
{
    public function __construct(
        Application $application,
    ) {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdo()
        );
    }

    public function duplicate(int $id, int $personId, Period $mode): ApiResponse
    {
        try {
            $this->pdo->beginTransaction();
            $sql = "
                SELECT
                    Id, Summary, Description, Location, StartTime, Duration,
                    IdEventType, CreatedBy, MaxParticipants, Audience
                FROM Event
                WHERE Id = :id
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            /** @var stdClass|false $event */
            $event = $stmt->fetch(PDO::FETCH_OBJ);
            if ($event === false) {
                $this->pdo->rollBack();
                return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Unknown event');
            }
            $eventRow = EventFullRow::fromStdClass($event);

            $newStartTime = $this->calculateNewStartTime($eventRow->startTime, $mode);
            $newEvent = [
                'Summary'         => $eventRow->summary,
                'Description'     => $eventRow->description,
                'Location'        => $eventRow->location,
                'StartTime'       => $newStartTime,
                'Duration'        => $eventRow->duration,
                'IdEventType'     => $eventRow->idEventType,
                'CreatedBy'       => $personId,
                'MaxParticipants' => $eventRow->maxParticipants,
                'Audience'        => $eventRow->audience
            ];
            $newEventId = $this->set('Event', $newEvent);
            $attributes = $this->gets('EventAttribute', ['IdEvent' => $id], 'IdAttribute');
            foreach ($attributes as $attr) {
                $this->set('EventAttribute', [
                    'IdEvent'     => $newEventId,
                    'IdAttribute' => $attr->IdAttribute,
                ]);
            }
            $this->pdo->commit();
            return new ApiResponse(true, ApplicationError::Ok->value, ['newEventId' => $newEventId]);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return new ApiResponse(false, ApplicationError::Error->value, [], 'Error: ' . $e->getMessage());
        }
    }

    public function eventExists(int $eventId): bool
    {
        $sql = "SELECT Id FROM Event WHERE Id = :eventId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);
        return $stmt->fetch(PDO::FETCH_OBJ) !== false;
    }

    public function findAudience(int $eventId): ?EventAudienceRow
    {
        $row = $this->get('Event', ['Id' => $eventId], 'Id, Audience');

        return $row === false ? null : EventAudienceRow::fromStdClass($row);
    }

    public function findChatRow(int $eventId): ?EventChatRow
    {
        $row = $this->get('Event', ['Id' => $eventId], 'CreatedBy, Summary, Id, StartTime, Duration, Location');

        return $row === false ? null : EventChatRow::fromRow($row);
    }

    public function findRegistrationInfo(int $eventId): ?RegistrationEventInfo
    {
        $row = $this->get('Event', ['Id' => $eventId], 'Summary, StartTime, Location');
        if ($row === false) {
            return null;
        }

        /** @var object{Summary: mixed, StartTime: mixed, Location: mixed} $row */
        return RegistrationEventInfo::fromStdClass($row);
    }

    public function getEvent(int $eventId): EventDetailRow
    {
        if ($this->eventExists($eventId)) {
            $sql = "
                SELECT
                    e.Id, e.Summary, e.Description, e.Location, e.StartTime, e.Duration,
                    e.IdEventType, e.CreatedBy, e.MaxParticipants, e.Audience, e.LastUpdate, e.Canceled,
                    et.Name AS EventTypeName
                FROM Event e
                INNER JOIN EventType et ON e.IdEventType = et.Id
                WHERE e.Id = :eventId
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':eventId' => $eventId]);
            /** @var stdClass|false $result */
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            if ($result === false) {
                throw new QueryException("Event type doesn't exist for event ({$eventId})");
            }

            return EventDetailRow::fromStdClass($result);
        }

        throw new QueryException("Event ({$eventId}) doesn't exist");
    }

    public function getEventExternal(int $eventId): ?EventExternalRow
    {
        $sql = "
            SELECT Id, Summary, Description, Location, StartTime, Audience
            FROM Event
            WHERE Id = :eventId
              AND (Audience = 'All' OR Audience = 'Guest')
              AND StartTime > :today
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':eventId' => $eventId,
            ':today'   => (new DateTime())->format('Y-m-d\TH:i:s'),
        ]);
        /** @var EventExternalRowShape|false $result */
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        if ($result === false) {
            return null;
        }
        return EventExternalRow::fromStdClass($result);
    }

    public function getEventGroup(int $eventId): ?int
    {
        if ($this->eventExists($eventId)) {
            $sql = "
                SELECT et.IdGroup AS IdGroup
                FROM EventType et
                INNER JOIN Event e ON et.Id = e.IdEventType
                WHERE e.Id = :eventId
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':eventId' => $eventId]);
            /** @var object{IdGroup: int|string|null}|false $result */
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            if ($result === false || $result->IdGroup === null) {
                return null;
            }
            return (int)$result->IdGroup;
        }
        throw new QueryException("Event ({$eventId}) doesn't exist");
    }

    /** @return array<int, mixed> */
    public function removeParticipant(int $id, int $personId): array
    {
        if (!$this->get('Event', ['Id' => $id, 'CreatedBy' => $personId], 'Id')) {
            return [['success' => false, 'message' => 'User not allowed'], ApplicationError::Forbidden->value];
        }
        if ($this->gets('Participant', ['IdEvent' => $id], 'Id')) {
            $this->set('Event', ['Canceled' => 1], ['Id' => $id]);
            return [['success' => true, 'message' => 'Evénement annulé'], ApplicationError::Unauthorized->value];
        }
        try {
            $this->pdo->beginTransaction();

            $this->delete('EventAttribute', ['IdEvent' => $id]);
            $this->delete('EventNeed', ['IdEvent' => $id]);
            $this->delete('Event', ['Id' => $id]);
            $this->pdo->commit();
            return [true, [], ApplicationError::Ok->value];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [false, [
                'message' => 'Erreur lors de la suppression en base de données',
                'error' => $e->getMessage()
            ], ApplicationError::Error->value];
        }
    }


    /** @param array<string, mixed> $data */
    public function update(array $data, int $personId): void
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
        $this->pdo->beginTransaction();
        try {
            if ($data['formMode'] == 'create') {
                $newId = $this->set('Event', $values);
                if (!is_int($newId)) {
                    throw new QueryException('Failed to create event');
                }
                $eventId = $newId;
            } elseif ($data['formMode'] == 'update') {
                $eventId = To::int($data['id'] ?? null);
                if (!$this->get('Event', ['Id' => $eventId], 'Id')) {
                    throw new QueryException("Event {$eventId} doesn't exist");
                }
                $this->set('Event', $values, ['Id' => $data['id']]);
                $this->delete('EventAttribute', ['IdEvent' => $eventId]);
                $this->delete('EventNeed', ['IdEvent' => $eventId]);
            } else {
                Application::unreachable($data['formMode']);
            }
            $this->insertEventAttributes($eventId, $this->normalizeAttributeIds($data['attributes'] ?? null));
            $this->insertEventNeeds($eventId, $this->normalizeNeeds($data['needs'] ?? null));
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    #region Private functions
    private function calculateNewStartTime(string|DateTimeImmutable $originalStartTime, Period $mode): string
    {
        if (is_string($originalStartTime)) {
            $originalStartTime = str_replace('T', ' ', $originalStartTime);
            $from = new DateTimeImmutable($originalStartTime);
        } else {
            $from = $originalStartTime;
        }
        return $mode->next($from)->format('Y-m-d H:i:s');
    }

    /** @param array<int, int> $attributes */
    private function insertEventAttributes(int $eventId, array $attributes): void
    {
        if (!empty($attributes)) {
            foreach ($attributes as $attributeId) {
                $this->set('EventAttribute', [
                    'IdEvent'     => $eventId,
                    'IdAttribute' => $attributeId
                ]);
            }
        }
    }

    /** @param array<int, array{id: int, counter: int}> $needs */
    private function insertEventNeeds(int $eventId, array $needs): void
    {
        if (!empty($needs)) {
            foreach ($needs as $need) {
                $this->set('EventNeed', [
                    'IdEvent' => $eventId,
                    'IdNeed'  => $need['id'],
                    'Counter' => $need['counter'],
                ]);
            }
        }
    }

    /** @return array<int, int> */
    private function normalizeAttributeIds(mixed $attributes): array
    {
        if (!is_array($attributes)) {
            return [];
        }
        $result = [];
        foreach ($attributes as $attributeId) {
            if (is_scalar($attributeId)) {
                $result[] = (int)$attributeId;
            }
        }
        return $result;
    }

    /** @return array<int, array{id: int, counter: int}> */
    private function normalizeNeeds(mixed $needs): array
    {
        if (!is_array($needs)) {
            return [];
        }
        $result = [];
        foreach ($needs as $need) {
            if (
                is_array($need)
                && isset($need['id'], $need['counter'])
                && is_scalar($need['id'])
                && is_scalar($need['counter'])
            ) {
                $result[] = ['id' => (int)$need['id'], 'counter' => (int)$need['counter']];
            }
        }
        return $result;
    }
}
