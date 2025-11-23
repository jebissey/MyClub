<?php
declare(strict_types=1);

namespace app\models;


class LogDataStatisticsHelper extends Data
{
    public function getOsDistribution(): array
    {
        $sql = '
            SELECT Os, COUNT(*) AS count
            FROM Log
            GROUP BY Os
            ORDER BY count DESC
        ';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $labels = [];
        $data = [];
        foreach ($results as $row) {
            $labels[] = $row->Os ?: 'Inconnu';
            $data[] = (int)$row->count;
        }
        return ['labels' => $labels, 'data' => $data];
    }

    public function getBrowserDistribution(): array
    {
        $query = $this->pdoForLog->query("
            WITH RECURSIVE
            split(id, browser, word, rest, position) AS (
                SELECT rowid, Browser, '', Browser || ' ', 1
                FROM Log
                UNION ALL
                SELECT 
                    id,
                    browser,
                    CASE WHEN word = '' THEN SUBSTR(rest, 0, INSTR(rest, ' '))
                        ELSE word || ' ' || SUBSTR(rest, 0, INSTR(rest, ' '))
                    END,
                    LTRIM(SUBSTR(rest, INSTR(rest, ' '))),
                    position + 1
                FROM split
                WHERE rest != '' AND SUBSTR(rest, 0, INSTR(rest, ' ')) NOT GLOB '[0-9]*'
            )
            SELECT word AS Browser, COUNT(*) as count
            FROM split
            WHERE rest = '' OR SUBSTR(rest, 0, INSTR(rest, ' ')) GLOB '[0-9]*'
            GROUP BY word
            ORDER BY count DESC
        ");
        $results = $query->fetchAll();
        $labels = [];
        $data = [];
        foreach ($results as $row) {
            $labels[] = $row->Browser ?? 'Inconnu';
            $data[] = $row->count;
        }
        return ['labels' => $labels, 'data' => $data];
    }

    public function getScreenResolutionDistribution(): array
    {
        $sql = '
            SELECT ScreenResolution, COUNT(*) AS count
            FROM Log
            GROUP BY ScreenResolution
            ORDER BY count DESC
        ';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $typeGroups = [];
        $typeResolutions = [];

        foreach ($results as $row) {
            $orientation = $this->getScreenOrientation($row->ScreenResolution);
            $type = $this->getResolutionType($row->ScreenResolution);
            $emoji = $this->getDeviceEmoji($orientation, $type);

            $typeKey = "$type $emoji";
            if (!isset($typeGroups[$typeKey])) {
                $typeGroups[$typeKey] = 0;
                $typeResolutions[$typeKey] = [];
            }
            $typeGroups[$typeKey] += $row->count;
            if ($row->ScreenResolution && $row->ScreenResolution !== 'Inconnu') {
                $typeResolutions[$typeKey][] = $row->ScreenResolution;
            }
        }

        arsort($typeGroups);
        $labels = [];
        $data = [];
        foreach ($typeGroups as $typeKey => $count) {
            $label = $typeKey;
            if (!empty($typeResolutions[$typeKey])) {
                $resolutionRange = $this->getResolutionRange($typeResolutions[$typeKey]);
                $label .= " $resolutionRange";
            }
            $labels[] = $label;
            $data[] = $count;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getTypeDistribution(): array
    {
        $sql = '
            SELECT Type, COUNT(*) AS count
            FROM Log
            GROUP BY Type
            ORDER BY count DESC
        ';
        $stmt = $this->pdoForLog->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $labels = [];
        $data = [];
        foreach ($results as $row) {
            $labels[] = $row->Type ?: 'Inconnu';
            $data[] = $row->count;
        }
        return ['labels' => $labels, 'data' => $data];
    }

    #region Private helper methods
    private function getResolutionRange(array $resolutions): string
    {
        $widths = [];
        $heights = [];

        foreach ($resolutions as $resolution) {
            if (strpos($resolution, 'x') !== false) {
                [$width, $height] = explode('x', $resolution);
                $widths[] = (int)$width;
                $heights[] = (int)$height;
            }
        }

        if (empty($widths) || empty($heights)) return 'Résolutions variées';

        $minWidth = min($widths);
        $maxWidth = max($widths);
        $minHeight = min($heights);
        $maxHeight = max($heights);

        if ($minWidth === $maxWidth && $minHeight === $maxHeight) {
            return $minWidth . 'x' . $minHeight;
        }

        $widthRange = ($minWidth === $maxWidth) ? "[$minWidth]" : "[$minWidth-$maxWidth]";
        $heightRange = ($minHeight === $maxHeight) ? "[$minHeight]" : "[$minHeight-$maxHeight]";

        return $widthRange . 'x' . $heightRange;
    }

    private function getDeviceEmoji(string $orientation, string $type): string
    {
        return match (true) {
            str_contains($type, 'Mobile Premium') => '📱+',
            str_contains($type, 'Mobile Standard') => '📱',
            str_contains($type, 'Mobile Compact') => '📱-',
            str_contains($type, 'Mobile Basique') => '📞',
            str_contains($type, 'Tablette') => '📋',
            str_contains($type, '4K') => '🖥️+',
            str_contains($type, '2K'), str_contains($type, '1440p') => '🖥️',
            str_contains($type, 'HD') => '🖥️-',
            default => ($orientation === 'Portrait') ? '📱' : '🖥️',
        };
    }

    private function getScreenOrientation(?string $resolution): string
    {
        if (!$resolution || strpos($resolution, 'x') === false) return 'Inconnu';
        [$width, $height] = explode('x', $resolution);
        return ((int)$width < (int)$height) ? 'Portrait' : 'Paysage';
    }

    private function getResolutionType(?string $resolution): string
    {
        if (!$resolution) return 'Inconnu';
        [$width, $height] = explode('x', $resolution) + [0,0];
        $maxDimension = max((int)$width, (int)$height);

        return match (true) {
            $maxDimension >= 3840 => '4K',
            $maxDimension >= 2560 => '2K/1440p',
            $maxDimension >= 1920 => 'Full HD',
            $maxDimension >= 1280 => 'HD',
            $height > $width && $height >= 900 => 'Mobile Premium',
            $height > $width && $height >= 800 => 'Mobile Standard',
            $height > $width && $height >= 700 => 'Mobile Compact',
            $height > $width => 'Mobile Basique',
            $maxDimension >= 1024 => 'Tablette',
            default => 'Petit écran',
        };
    }
}
