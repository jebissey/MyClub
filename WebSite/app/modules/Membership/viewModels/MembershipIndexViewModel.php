<?php

declare(strict_types=1);

namespace app\modules\Membership\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class MembershipIndexViewModel extends LayoutViewModel
{
    /**
     * @param object{Id:int, PersonId:int, Season:string, Status:string, AmountCents:int}|false $current
     * @param array<int, stdClass> $history
     * @param array<string, string> $translations
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public string $season,
        public object|false $current,
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
            btn_HistoryBack: true,
            btn_Parent: '/user',
        );
    }
}
