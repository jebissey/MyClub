<?php

declare(strict_types=1);

namespace app\helpers;

use DateTime;
use app\enums\TimeOfDay;
use app\models\DataHelper;
use app\modules\Common\valueObjects\Person;
use app\modules\Event\valueObjects\EventListRow;

final readonly class PersonPreferences
{
    public function __construct(private DataHelper $dataHelper)
    {
    }

    /**
     * @param list<EventListRow> $events
     * @return list<EventListRow>
     */
    public function filterEventsByPreferences(array $events, ?Person $person): array
    {
        if ($person === null) {
            return $events;
        }

        $row = $this->dataHelper->get('Member', ['Id' => $person->id], 'Preferences, Availabilities');
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
            // Events without a start time cannot be matched against availabilities.
            if ($event->startTime === '') {
                continue;
            }

            $dayOfWeek = (int) (new DateTime($event->startTime))->format('N') - 1;

            if (
                $this->isPersonInterested(
                    $preferencesJson,
                    $availabilitiesJson,
                    $event->idEventType,
                    $dayOfWeek,
                    TimeOfDay::fromDateTime($event->startTime)->value
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
