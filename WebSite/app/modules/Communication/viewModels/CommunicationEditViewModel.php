<?php

declare(strict_types=1);

namespace app\modules\Communication\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class CommunicationEditViewModel extends LayoutViewModel
{
    /**
     * @param list<object> $groups
     * @param list<mixed> $navItems
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public array $groups,
        public array $navItems,
        public ?string $smtpFrom,
        public array $i18n,
        public ?int $connectedPersonId,
        public string $contactEmail,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
        );
    }
}
