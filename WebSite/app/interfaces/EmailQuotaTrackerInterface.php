<?php

declare(strict_types=1);

namespace app\interfaces;

interface EmailQuotaTrackerInterface
{
    public function getDailySent(): int;
    public function getMonthlySent(): int;
    public function increment(int $count = 1): void;
}