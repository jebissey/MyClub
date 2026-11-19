<?php

declare(strict_types=1);

namespace app\modules\Webmaster\services;

use app\helpers\To;

final class InstallationStatusFilter
{
    private const STATUS_CODES = ['active', 'recent', 'inactive'];

    /**
     * Builds the SQL condition matching the status codes whose translated label contains the filter.
     *
     * @param callable(string): mixed $translate Translation function
     */
    public function buildCondition(string $filter, callable $translate): string
    {
        $codes = [];
        foreach (self::STATUS_CODES as $code) {
            $label = To::str($translate('webmaster.installations.status.' . $code), '');
            if (mb_stripos($label, $filter) !== false) {
                $codes[] = "'" . $code . "'";
            }
        }

        return $codes === [] ? '1 = 0' : 'status IN (' . implode(', ', $codes) . ')';
    }
}
