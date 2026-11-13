<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\modules\Common\valueObjects\MemberContactRow;

final readonly class CopyEmailsViewModel extends LayoutViewModel
{
    /**
     * @param list<string> $emails
     * @param array<string, MemberContactRow> $people Active members indexed by email
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public string $emailsJson,
        public array $emails,
        public string $filters,
        public array $people,
        public array $i18n,
        array $layoutParams,
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: "/eventManager",
            btnPlus: false,
        );
    }
}
