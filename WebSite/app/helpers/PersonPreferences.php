<?php

declare(strict_types=1);

namespace app\helpers;

use DateTime;

class PersonPreferences
{
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
            if ($this->isPersonInterested($person, $event['idEventType'], (new DateTime($event['startTime']))->format('N') - 1, $this->getPeriodOfDay($event['startTime']))) {
                $filteredEvents[] = $event;
            }
        }
        return $filteredEvents;
    }

    public function isPersonInterested(object $person, ?int $idEventType,  ?int $dayOfWeek, $timeOfDay): bool
    {
        if ($person->Preferences != '') {
            $preferences = json_decode($person->Preferences, true);
            if (isset($preferences['noAlerts']) && $preferences['noAlerts'] == 'on') {
                return false;
            }
        }
        if ($idEventType !== null) {
            if ($person->Preferences != '') {
                $preferences = json_decode($person->Preferences, true);
                if ($preferences != '' && (!isset($preferences['eventTypes'][$idEventType]))) {
                    return false;
                }
                if (
                    $preferences != '' && (isset($preferences['eventTypes'][$idEventType]))
                    && !isset($preferences['eventTypes'][$idEventType]["available"])
                ) {
                    return true;
                }
            }
        }
        if ($dayOfWeek !== null && $timeOfDay != '') {
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

        if ($hour < 12)     return 'morning';
        elseif ($hour < 17) return 'afternoon';
        else                return 'evening';
    }
}
