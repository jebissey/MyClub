<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use RuntimeException;
use app\interfaces\EmailQuotaTrackerInterface;

final class JsonEmailQuotaTracker implements EmailQuotaTrackerInterface
{
    private string $today;
    private string $thisMonth;
    private array  $data;

    public function __construct(private readonly string $filePath)
    {
        $this->today     = date('Y-m-d');
        $this->thisMonth = date('Y-m');
        $this->data      = $this->load();
    }

    public function getDailySent(): int
    {
        return $this->data['daily'];
    }

    public function getMonthlySent(): int
    {
        return $this->data['monthly'];
    }

    public function increment(int $count = 1): void
    {
        $this->data['daily']   += $count;
        $this->data['monthly'] += $count;
        $this->save();
    }

    # Private functions
    private function load(): array
    {
        $defaults = [
            'day'     => $this->today,
            'month'   => $this->thisMonth,
            'daily'   => 0,
            'monthly' => 0,
        ];

        if (!file_exists($this->filePath)) {
            return $defaults;
        }
        $raw = file_get_contents($this->filePath);
        if ($raw === false) {
            throw new RuntimeException("Cannot read quota file: {$this->filePath}");
        }
        $stored = json_decode($raw, true);
        if (!is_array($stored)) {
            return $defaults;
        }
        if (($stored['day'] ?? '') !== $this->today) {
            $stored['daily'] = 0;
            $stored['day']   = $this->today;
        }
        if (($stored['month'] ?? '') !== $this->thisMonth) {
            $stored['monthly'] = 0;
            $stored['month']   = $this->thisMonth;
        }

        return $stored;
    }

    private function save(): void
    {
        $dir = dirname($this->filePath);

        if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
            throw new RuntimeException("Cannot create quota directory: {$dir}");
        }

        $result = file_put_contents(
            $this->filePath,
            json_encode($this->data, JSON_PRETTY_PRINT),
            LOCK_EX
        );

        if ($result === false) {
            throw new RuntimeException("Cannot write quota file: {$this->filePath}");
        }
    }
}
