<?php

declare(strict_types=1);

namespace app\modules\Membership\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Membership\valueObjects\MembershipRow;

final readonly class MembershipIndexViewModel extends LayoutViewModel
{
    /**
     * @param list<MembershipRow> $history
     * @param array<string, string> $translations
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public string $season,
        public MembershipRow|false $current,
        public array $history,
        public int $amountCents,
        public ?string $paymentFeedback,
        public array $translations,
        public string $activeTab,
        public ?string $widgetUrl,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/user',
            btnPlus: false,
        );
    }
}
