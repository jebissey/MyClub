<?php

declare(strict_types=1);

namespace app\helpers;

use DateTime;
use app\valueObjects\Person;

class PersonPreferences
{
    /**
     * @param array<int, array<string, mixed>> $events
     * @return array<int, array<string, mixed>>
     */
    public function filterEventsByPreferences(array $events, ?Person $person): array
    {
        if ($person === null || empty($person->Preferences)) {
            return $events;
        }
        $preferences = json_decode($person->Preferences, true);
        if (!is_array($preferences)) {
            return $events;
        }

        $filteredEvents = [];
        foreach ($events as $event) {
            $idEventType = $event['idEventType'] ?? null;
            $idEventType = is_int($idEventType)
                ? $idEventType
                : (is_numeric($idEventType) ? (int)$idEventType : null);

            $startTime = $event['startTime'] ?? null;
            if (!is_string($startTime) || $startTime === '') {
                continue;
            }

            $dayOfWeek = (int)(new DateTime($startTime))->format('N') - 1;

            if (
                $this->isPersonInterested(
                    $person,
                    $idEventType,
                    $dayOfWeek,
                    $this->getPeriodOfDay($startTime)
                )
            ) {
                $filteredEvents[] = $event;
            }
        }
        return $filteredEvents;
    }

    public function isPersonInterested(Person $person, ?int $idEventType, ?int $dayOfWeek, string $timeOfDay): bool
    {
        if ($person->Preferences !== '') {
            $preferences = $this->decodePreferences($person->Preferences);
            if ($preferences !== null && isset($preferences['noAlerts']) && $preferences['noAlerts'] === 'on') {
                return false;
            }
        }

        if ($idEventType !== null && $person->Preferences !== '') {
            $preferences = $this->decodePreferences($person->Preferences);
            if ($preferences !== null) {
                $eventTypes = is_array($preferences['eventTypes'] ?? null) ? $preferences['eventTypes'] : null;
                if ($eventTypes !== null) {
                    if (!isset($eventTypes[$idEventType])) {
                        return false;
                    }
                    $eventTypePref = $eventTypes[$idEventType];
                    if (is_array($eventTypePref) && !isset($eventTypePref['available'])) {
                        return true;
                    }
                }
            }
        }

        if ($dayOfWeek !== null && $timeOfDay !== '' && $person->Availabilities !== '') {
            $availabilities = $this->decodePreferences($person->Availabilities);
            if ($availabilities !== null) {
                $dayPrefs = is_array($availabilities[$dayOfWeek] ?? null) ? $availabilities[$dayOfWeek] : null;
                if ($dayPrefs !== null && ($dayPrefs[$timeOfDay] ?? null) !== 'on') {
                    return false;
                }
            }
        }

        return true;
    }

    public function getPeriodOfDay(string $dateString): string
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

    /**
     * @return array<int|string, mixed>|null
     */
    private function decodePreferences(?string $json): ?array
    {
        if ($json === null || $json === '') {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
