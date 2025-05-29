<?php

namespace app\helpers;

use DateInterval;
use DateTime;
use PDO;

class Event
{
    private PDO $pdo;
    private $fluent;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
    }

    public function getEventsForDay($date, $userEmail)
    {
        $query = $this->pdo->prepare("
            SELECT DISTINCT e.*, et.Name as EventTypeName
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN Person p ON p.Email = :userEmail
            LEFT JOIN PersonGroup pg ON pg.IdPerson = p.Id
            WHERE DATE(e.StartTime) = :date
            AND (  et.IdGroup IN (SELECT pg.IdGroup FROM PersonGroup pg WHERE pg.IdPerson = ? AND pg.IdGroup = et.IdGroup)
                OR et.IdGroup is NULL)
            ORDER BY e.StartTime");
        $query->execute([
            'date' => $date,
            'userEmail' => $userEmail
        ]);

        return $query->fetchAll();
    }

    public function isUserRegistered($eventId, $userEmail)
    {
        $result = $this->fluent->from('Participant pa')
            ->select('COUNT(*) AS count')
            ->join('Person pe ON pa.IdPerson = pe.Id')
            ->where('pa.IdEvent', $eventId)
            ->where('pe.Email', $userEmail)
            ->fetch();
        return ($result->count > 0);
    }

    public function getNextEvents($person)
    {
        $now = date('Y-m-d H:i:s');
        $query = $this->fluent
            ->from('Event e')
            ->leftJoin('EventType et ON e.IdEventType = et.Id')
            ->leftJoin('Participant p ON e.Id = p.IdEvent AND p.IdPerson = ?', $person->Id ?? 0)
            ->leftJoin('Message m ON m.EventId = e.Id')
            ->where('e.StartTime > ?', $now)
            ->groupBy('e.Id');

        if ($person === false) {
            $query->where("e.Audience = '" . EventAudience::ForAll->value . "'");
            $query->where("et.IdGroup IS NULL");
        } else {
            $query->where("et.IdGroup IS NULL OR et.IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ?)", $person->Id);
        }

        $query->select('COUNT(m.Id) AS MessageCount');
        $query->select('et.Name AS EventTypeName, et.IdGroup AS EventTypeIdGroup, p.Id As Booked')
            ->orderBy('e.StartTime');

        return $this->events($query->fetchAll());
    }


    public function getEvent($eventId)
    {
        return $this->fluent->from('Event e')
            ->select('e.*, et.Name AS EventTypeName')
            ->join('EventType et ON e.IdEventType = et.Id')
            ->where('e.Id', $eventId)
            ->fetch();
    }

    public function getEventAttributes($eventId)
    {
        return $this->fluent->from('EventAttribute ea')
            ->select('a.Name, a.Detail, a.Color')
            ->join('Attribute a ON ea.IdAttribute = a.Id')
            ->where('ea.IdEvent', $eventId)
            ->fetchAll();
    }

    public function getEventParticipants($eventId)
    {
        return $this->fluent->from('Participant pa')
            ->select('pe.FirstName, pe.LastName, pe.NickName, pe.Email')
            ->join('Person pe ON pa.IdPerson = pe.Id')
            ->where('pa.IdEvent', $eventId)
            ->orderBy('pe.FirstName, pe.LastName')
            ->fetchAll();
    }

    public function getEvents($person, string $mode, int $offset): array
    {
        if ($mode == 'next') {
            return $this->getNextEvents($person);
        } else if ($mode === 'past') {
            $limit = 10;
            $query = $this->fluent->from('Event e')
                ->leftJoin('EventType et ON et.Id = e.IdEventType')
                ->leftJoin('Message m ON m.EventId = e.Id')
                ->where('StartTime < ?', date('Y-m-d H:i:s'))
                ->groupBy('e.Id')
                ->limit($limit)
                ->offset($offset)
                ->select('et.Name AS EventTypeName, et.IdGroup AS EventTypeIdGroup, 0 AS Booked')
                ->select('COUNT(m.Id) AS MessageCount')
                ->orderBy('e.StartTime');
            return $this->events($query->fetchAll());
        } else {
            die("Invalide mode ($mode)");
        }
    }


    public function getNextWeekEvents(): array
    {
        $startOfCurrentWeek = new DateTime();
        $startOfCurrentWeek->setISODate(
            (int)$startOfCurrentWeek->format('o'),
            (int)$startOfCurrentWeek->format('W')
        );

        $endOfNextWeek = clone $startOfCurrentWeek;
        $endOfNextWeek->add(new DateInterval('P14D')); // +14 jours

        // Requête pour récupérer les événements des 2 prochaines semaines
        $events = $this->fluent->from('Event e')
            ->select([
                'e.Id',
                'e.Summary',
                'e.Description',
                'e.Location',
                'e.StartTime',
                'e.Duration',
                'e.IdEventType',
                'et.Name AS EventTypeName',
                'GROUP_CONCAT(a.Id) AS AttributeIds',
                'GROUP_CONCAT(a.Name) AS AttributeNames',
                'GROUP_CONCAT(a.Detail) AS AttributeDetails',
                'GROUP_CONCAT(a.Color) AS AttributeColors'
            ])
            ->innerJoin('EventType et ON e.IdEventType = et.Id')
            ->leftJoin('EventAttribute ea ON e.Id = ea.IdEvent')
            ->leftJoin('Attribute a ON ea.IdAttribute = a.Id')
            ->where('datetime(e.StartTime) >= ?', $startOfCurrentWeek->format('Y-m-d H:i:s'))
            ->where('datetime(e.StartTime) < ?', $endOfNextWeek->format('Y-m-d H:i:s'))
            ->groupBy('e.Id')
            ->orderBy('e.StartTime')
            ->fetchAll();


        // Organiser les événements par semaine et par jour
        $weeklyEvents = [];

        foreach ($events as $event) {
            $eventDate = new DateTime($event['StartTime']);
            $weekNumber = (int)$eventDate->format('W');
            $year = (int)$eventDate->format('o');
            $dayOfWeek = (int)$eventDate->format('N'); // 1=Lundi, 7=Dimanche

            // Calculer les dates de début et fin de semaine
            $startOfWeek = new DateTime();
            $startOfWeek->setISODate($year, $weekNumber);
            $endOfWeek = clone $startOfWeek;
            $endOfWeek->add(new DateInterval('P6D'));

            $weekKey = $startOfWeek->format('Y-W');

            if (!isset($weeklyEvents[$weekKey])) {
                $weeklyEvents[$weekKey] = [
                    'weekStart' => $startOfWeek->format('d/m'),
                    'weekEnd' => $endOfWeek->format('d/m'),
                    'weekStartFull' => $startOfWeek->format('Y-m-d'),
                    'days' => array_fill(1, 7, []) // Jours 1-7 (Lundi-Dimanche)
                ];
            }

            // Traiter les attributs
            $attributes = [];
            if (!empty($event['AttributeIds'])) {
                $ids = explode(',', $event['AttributeIds']);
                $names = explode(',', $event['AttributeNames']);
                $details = explode(',', $event['AttributeDetails']);
                $colors = explode(',', $event['AttributeColors']);

                for ($i = 0; $i < count($ids); $i++) {
                    $attributes[] = [
                        'id' => $ids[$i],
                        'name' => $names[$i] ?? '',
                        'detail' => $details[$i] ?? '',
                        'color' => $colors[$i] ?? '#cccccc'
                    ];
                }
            }

            // Formater l'heure et la durée
            $startTime = new DateTime($event['StartTime']);
            $durationMinutes = $event['Duration'] / 60;
            $durationFormatted = '';

            if ($durationMinutes >= 60) {
                $hours = floor($durationMinutes / 60);
                $minutes = $durationMinutes % 60;
                $durationFormatted = $hours . 'h';
                if ($minutes > 0) {
                    $durationFormatted .= sprintf('%02d', $minutes);
                }
            } else {
                $durationFormatted = $durationMinutes . 'min';
            }

            $eventFormatted = [
                'id' => $event['Id'],
                'summary' => $event['Summary'],
                'description' => $event['Description'],
                'location' => $event['Location'],
                'startTime' => $startTime->format('H:i'),
                'duration' => $durationFormatted,
                'eventType' => $event['EventTypeName'],
                'attributes' => $attributes,
                'fullDateTime' => $event['StartTime']
            ];

            $weeklyEvents[$weekKey]['days'][$dayOfWeek][] = $eventFormatted;
        }

        // Trier par semaine
        ksort($weeklyEvents);

        return $weeklyEvents;
    }


    #region Private functions
    private function events($events): array
    {
        $eventIds = array_column($events, 'Id');
        $attributes = [];
        if (!empty($eventIds)) {
            $rows = $this->fluent->from('EventAttribute ea')
                ->select('ea.IdEvent, a.Id, a.Name, a.Detail, a.Color')
                ->join('Attribute a ON ea.IdAttribute = a.Id')
                ->where('ea.IdEvent', $eventIds)
                ->fetchAll();

            foreach ($rows as $row) {
                $attributes[$row->IdEvent][] = [
                    'id' => $row->Id,
                    'name' => $row->Name,
                    'detail' => $row->Detail,
                    'color' => $row->Color
                ];
            }
        }
        return array_map(function ($event) use ($attributes) {
            return [
                'id' => $event->Id,
                'eventTypeName' => $event->EventTypeName,
                'groupName' => $event->EventTypeIdGroup ? $this->fluent->from("'Group'")->where('Id', $event->EventTypeIdGroup)->fetch('Name') : '',
                'summary' => $event->Summary,
                'location' => $event->Location,
                'startTime' => $event->StartTime,
                'duration' => (new TranslationManager($this->pdo))->getReadableDuration($event->Duration),
                'attributes' => $attributes[$event->Id] ?? [],
                'participants' => $this->fluent->from('Participant')->where('IdEvent', $event->Id)->count(),
                'maxParticipants' => $event->MaxParticipants,
                'booked' => $event->Booked,
                'audience' => $event->Audience,
                'createdBy' => $event->CreatedBy,
                'messages' => $event->MessageCount,
            ];
        }, $events);
    }
}
