<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class AvailabilityDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * @return array{
     *   totalActive: int,
     *   withAvailability: int,
     *   percentageFilled: float,
     *   byDay: array<int, array{morning: float, afternoon: float, evening: float}>
     * }
     */
    public function getAvailabilityStats(): array
    {
        $totalActiveStmt = $this->pdo->query(
            'SELECT COUNT(*) FROM Member WHERE Inactivated = 0'
        );
        if ($totalActiveStmt === false) {
            throw new \RuntimeException('Failed to query total active members');
        }
        $totalActive = (int) $totalActiveStmt->fetchColumn();

        $stmt = $this->pdo->query(
            'SELECT Availabilities FROM Member WHERE Inactivated = 0 AND Availabilities IS NOT NULL AND Availabilities != \'\''
        );
        if ($stmt === false) {
            throw new \RuntimeException('Failed to query member availabilities');
        }
        $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $withAvailability = count($rows);

        $counts = array_fill(0, 7, [
            'morning'   => 0,
            'afternoon' => 0,
            'evening'   => 0,
        ]);

        foreach ($rows as $json) {
            $normalized = $this->normalizeAvailabilities($json);

            for ($day = 0; $day < 7; $day++) {
                $slots = $normalized[$day];
                foreach (['morning', 'afternoon', 'evening'] as $slot) {
                    if (($slots[$slot] ?? null) === 'on') {
                        $counts[$day][$slot]++;
                    }
                }
            }
        }

        $byDay = [];
        $divisor = max($withAvailability, 1);

        for ($day = 0; $day < 7; $day++) {
            $byDay[$day] = [
                'morning'   => round($counts[$day]['morning']   * 100 / $divisor, 1),
                'afternoon' => round($counts[$day]['afternoon'] * 100 / $divisor, 1),
                'evening'   => round($counts[$day]['evening']   * 100 / $divisor, 1),
            ];
        }

        return [
            'totalActive'       => $totalActive,
            'withAvailability'  => $withAvailability,
            'percentageFilled'  => $totalActive > 0
                ? round($withAvailability * 100 / $totalActive, 1)
                : 0.0,
            'byDay'             => $byDay,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function normalizeAvailabilities(mixed $json): array
    {
        $data = is_string($json) ? json_decode($json, true) : $json;
        if (!is_array($data)) {
            return array_fill(0, 7, []);
        }

        // Cas 1 : tableau indexé [0..6]
        if (array_is_list($data) && count($data) === 7) {
            $result = [];
            foreach ($data as $day => $slots) {
                $result[$day] = $this->sanitizeSlots($slots);
            }
            return $result;
        }

        // Cas 2 : objet associatif {"0": {...}, "5": {...}, ...}
        $result = array_fill(0, 7, []);
        foreach ($data as $day => $slots) {
            $day = (int) $day;
            if ($day >= 0 && $day <= 6) {
                $result[$day] = $this->sanitizeSlots($slots);
            }
        }
        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function sanitizeSlots(mixed $slots): array
    {
        if (!is_array($slots)) {
            return [];
        }

        $result = [];
        foreach ($slots as $key => $value) {
            if (is_string($value)) {
                $result[(string) $key] = $value;
            }
        }
        return $result;
    }
}
