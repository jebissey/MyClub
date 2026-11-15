<?php

declare(strict_types=1);

namespace app\modules\Webmaster\services;

use app\helpers\To;
use app\modules\Common\valueObjects\TableRow;

/**
 * Adds display fields (resolved host, time ago, translated status) to installation rows.
 */
final class InstallationDisplayService
{
    /**
     * Call it on the current page only, to limit reverse DNS lookups.
     *
     * @param list<TableRow> $rows
     * @param callable(string): string $t Translator
     * @return list<TableRow>
     */
    public function enrich(array $rows, callable $t): array
    {
        /** @var array<string, string|false> $dnsCache */
        $dnsCache = [];
        $unknownVersion = $t('webmaster.installations.unknown_version');

        $enriched = [];
        foreach ($rows as $row) {
            $cells = $row->cells;

            $cells['Host']           = $this->resolveHost(To::str($cells['Host'] ?? ''), $dnsCache);
            $cells['webappVersions'] = str_replace(
                ',',
                ', ',
                To::str($cells['webappVersions'] ?? $unknownVersion)
            );
            $cells['phpVersions'] = str_replace(
                ',',
                ', ',
                To::str($cells['phpVersions'] ?? $unknownVersion)
            );

            $lastCheck         = To::str($cells['lastCheck'] ?? '');
            $cells['timeAgo']  = $this->getTimeAgo($lastCheck);
            $cells['status']   = $t($this->statusKey($lastCheck));

            $enriched[] = new TableRow($row->id, $cells);
        }

        return $enriched;
    }

    /**
     * @param array<string, string|false> $dnsCache
     */
    private function resolveHost(string $host, array &$dnsCache): string
    {
        if (str_contains($host, '%')) {
            $decoded    = urldecode($host);
            $parsedHost = parse_url($decoded, PHP_URL_HOST);
            return is_string($parsedHost) && $parsedHost !== '' ? $parsedHost : $decoded;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $dnsCache[$host] ??= @gethostbyaddr($host);
            $hostname = $dnsCache[$host];
            if ($hostname !== false && $hostname !== $host) {
                return $hostname;
            }
        }

        return $host;
    }

    private function statusKey(string $lastCheck): string
    {
        $timestamp = strtotime($lastCheck);
        if ($timestamp === false) {
            return 'webmaster.installations.status.inactive';
        }

        $daysSinceLastCheck = (time() - $timestamp) / 86400;

        return match (true) {
            $daysSinceLastCheck < 7  => 'webmaster.installations.status.active',
            $daysSinceLastCheck < 30 => 'webmaster.installations.status.recent',
            default                  => 'webmaster.installations.status.inactive',
        };
    }


    private function getTimeAgo(string $datetime): string
    {
        $time = time() - strtotime($datetime);

        if ($time < 60) {
            return 'Il y a ' . $time . ' secondes';
        }

        if ($time < 3600) {
            return 'Il y a ' . round($time / 60) . ' minutes';
        }

        if ($time < 86400) {
            return 'Il y a ' . round($time / 3600) . ' heures';
        }

        if ($time < 2592000) {
            return 'Il y a ' . round($time / 86400) . ' jours';
        }

        if ($time < 31536000) {
            return 'Il y a ' . round($time / 2592000) . ' mois';
        }

        return 'Il y a ' . round($time / 31536000) . ' ans';
    }
}
