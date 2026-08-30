<?php

declare(strict_types=1);

namespace app\modules\VisitorInsights\viewModels;

use app\helpers\WebApp;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class AnalyticsViewModel extends LayoutViewModel
{
    /**
     * @param mixed $osData
     * @param mixed $browserData
     * @param mixed $screenResolutionData
     * @param mixed $typeData
     * @param mixed $nav
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public mixed $osData,
        public mixed $browserData,
        public mixed $screenResolutionData,
        public mixed $typeData,
        public string $title,
        public WebApp $control,
        public string $period,
        public mixed $nav,
        public array $i18n,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/admin',
        );
    }
}
