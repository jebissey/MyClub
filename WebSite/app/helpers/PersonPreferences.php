<?php

namespace app\helpers;

use DateTime;
use PDO;

class PersonPreferences
{
    private PDO $pdo;
    private $fluent;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
    }

    public function filterEventsByPreferences(array $events, $person): array
    {
        if (!$person || empty($person->Preferences)) {
            return $events;
        }
        $preferences = json_decode($person->Preferences, true);
        if (!$preferences) {
            return $events;
        }
        $filteredEvents = [];
        foreach ($events as $event) {
            if ($this->isPersonInterested($person, $event['idEventType'], new DateTime($event['startTime'])->format('N') - 1, $this->getPeriodOfDay($event['startTime']))) {
                $filteredEvents[] = $event;
            }
        }
        return $filteredEvents;
    }

    public function isPersonInterested($person, $idEventType,  $dayOfWeek, $timeOfDay): bool
    {
        if ($person->Preferences != '') {
            $preferences = json_decode($person->Preferences, true);
            if (isset($preferences['noAlerts']) && $preferences['noAlerts'] == 'on') {
                return false;
            }
        }
        if (!empty($idEventType)) {
            if ($person->Preferences != '') {
                $preferences = json_decode($person->Preferences, true);
                if ($preferences != '' && (!isset($preferences['eventTypes'][$idEventType]))) {
                    return false;
                }
            }
        }
        if ($dayOfWeek != '' && $timeOfDay != '') {
            if ($person->Availabilities != '') {
                $availabilities = json_decode($person->Availabilities, true);
                if (isset($availabilities[$dayOfWeek][$timeOfDay]) != 'on') {
                    return false;
                }
            }
        }
        return true;
    }

    public function getPeriodOfDay($dateString)
    {
        $date = new DateTime($dateString);
        $hour = (int)$date->format('H');

        if ($hour < 12) {
            return 'morning';
        } elseif ($hour < 17) {
            return 'afternoon';
        } else {
            return 'evening';
        }
    }
}
