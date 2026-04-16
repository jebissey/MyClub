<?php

declare(strict_types=1);

namespace app\helpers;

use RuntimeException;


class DistributionCalculator
{
    private const SLICES = 20;

    /**
     * @param array<string, int> $memberCounts  email => count
     * @return array{tranches: array, distribution: array, memberCounts: array}
     */
    public function compute(array $memberCounts): array
    {
        $counts = array_values($memberCounts);

        if (empty($counts)) {
            return ['tranches' => [], 'distribution' => [], 'memberCounts' => []];
        }

        $min        = min($counts);
        $max        = max($counts);
        $sliceSize  = max(1, (int) ceil(($max - $min) / self::SLICES));

        $tranches = $this->buildTranches($min, $max, $sliceSize);
        $distribution = $this->buildDistribution($memberCounts, $tranches, $min, $sliceSize);

        [$mergedTranches, $mergedDistribution] = $this->mergeEmptySlices($tranches, $distribution);

        return [
            'tranches'     => $mergedTranches,
            'distribution' => $mergedDistribution,
            'memberCounts' => $memberCounts,
        ];
    }

    public function findUserSlice(array $tranches, array $memberCounts, string $email): int
    {
        if (!array_key_exists($email, $memberCounts)) {
            throw new RuntimeException("User $email not found in distribution data.");
        }

        $userCount = $memberCounts[$email];

        foreach ($tranches as $i => $tranche) {
            if ($userCount >= $tranche['start'] && $userCount <= $tranche['end']) {
                return $i;
            }
        }

        throw new RuntimeException("No slice found for user $email.");
    }

    #region Private functions
    private function buildTranches(int $min, int $max, int $sliceSize): array
    {
        $tranches = [];
        for ($i = 0; $i < self::SLICES; $i++) {
            $start = $min + ($i * $sliceSize);
            $end   = ($i === self::SLICES - 1) ? $max : $start + $sliceSize - 1;
            $tranches[] = ['start' => $start, 'end' => $end, 'label' => "$start-$end"];
        }
        return $tranches;
    }

    private function buildDistribution(array $memberCounts, array $tranches, int $min, int $sliceSize): array
    {
        $distribution = array_fill(0, count($tranches), 0);
        foreach ($memberCounts as $count) {
            $index = (int) floor(($count - $min) / $sliceSize);
            $index = min($index, self::SLICES - 1);
            $distribution[$index]++;
        }
        return $distribution;
    }

    private function mergeEmptySlices(array $tranches, array $distribution): array
    {
        $mergedTranches     = [];
        $mergedDistribution = [];
        $currentTranche     = null;

        foreach ($tranches as $i => $tranche) {
            if ($distribution[$i] === 0) {
                if ($currentTranche === null) {
                    $currentTranche = $tranche;
                } else {
                    $currentTranche['end']   = $tranche['end'];
                    $currentTranche['label'] = "{$currentTranche['start']}-{$currentTranche['end']}";
                }
            } else {
                if ($currentTranche !== null) {
                    $mergedTranches[]     = $currentTranche;
                    $mergedDistribution[] = 0;
                    $currentTranche       = null;
                }
                $mergedTranches[]     = $tranche;
                $mergedDistribution[] = $distribution[$i];
            }
        }

        if ($currentTranche !== null) {
            $mergedTranches[]     = $currentTranche;
            $mergedDistribution[] = 0;
        }

        return [$mergedTranches, $mergedDistribution];
    }
}
