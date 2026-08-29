<?php

declare(strict_types=1);

namespace app\helpers;

use DateTime;
use app\models\DataHelper;
use app\valueObjects\Person;

class PersonPreferences
{
    public function __construct(private DataHelper $dataHelper)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array<int, array<string, mixed>>
     */
    public function filterEventsByPreferences(array $events, ?Person $person): array
    {
        if ($person === null) {
            return $events;
        }

        $row = $this->dataHelper->get('Person', ['Id' => $person->Id], 'Preferences, Availabilities');
        /** @var object{Preferences: string|null, Availabilities: string|null}|false $row */
        if ($row === false) {
            return $events;
        }
        $preferencesJson = $row->Preferences ?? '';
        $availabilitiesJson = $row->Availabilities ?? '';

        if (empty($preferencesJson) && empty($availabilitiesJson)) {
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
                    $preferencesJson,
                    $availabilitiesJson,
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

    public function isPersonInterested(
        ?string $preferencesJson,
        ?string $availabilitiesJson,
        ?int $idEventType,
        ?int $dayOfWeek,
        string $timeOfDay
    ): bool {
        if (!empty($preferencesJson)) {
            $preferences = $this->decodePreferences($preferencesJson);
            if ($preferences !== null && isset($preferences['noAlerts']) && $preferences['noAlerts'] === 'on') {
                return false;
            }
        }

        if ($idEventType !== null && !empty($preferencesJson)) {
            $preferences = $this->decodePreferences($preferencesJson);
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

        if ($dayOfWeek !== null && $timeOfDay !== '' && !empty($availabilitiesJson)) {
            $availabilities = $this->decodePreferences($availabilitiesJson);
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
