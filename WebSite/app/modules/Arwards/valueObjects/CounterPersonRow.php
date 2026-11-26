<?php

declare(strict_types=1);

namespace app\modules\Arwards\valueObjects;

final readonly class CounterPersonRow
{
    /**
     * @param array<string, int|float> $counters
     */
    public function __construct(
        public string $name,
        public array $counters,
        public int|float $total,
    ) {
    }
}
