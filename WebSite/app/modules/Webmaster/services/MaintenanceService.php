<?php

declare(strict_types=1);

namespace app\modules\Webmaster\services;

use app\models\Data;

final class MaintenanceService
{
    private const MAINTENANCE_UNSET = '/maintenance/unset';

    public function __construct(
        private Data $dataHelper,
    ) {
    }

    public function checkIfSiteIsUnderMaintenance(): bool
    {
        error_log("\n\n" . json_encode('---###---', JSON_PRETTY_PRINT) . "\n");
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        if (is_string($requestUri) && strpos($requestUri, self::MAINTENANCE_UNSET) !== false) {
            return false;
        }

        $siteUnderMaintenance = $this->dataHelper->get('Metadata', ['Id' => 1], 'SiteUnderMaintenance');
        if ($siteUnderMaintenance === false) {
            return false;
        }

        /** @var object{SiteUnderMaintenance: int} $siteUnderMaintenance */
        return $siteUnderMaintenance->SiteUnderMaintenance !== 0;
    }
}
