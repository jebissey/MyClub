<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class CopyEmailsViewModel extends LayoutViewModel
{
    /**
     * @param list<string> $emails
     * @param array<string, mixed> $people Replace with the return type of MemberDirectoryDataHelper::getActiveMembersContactInfoByEmail()
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
        parent::__construct(...self::baseArgsFrom($layoutParams));
    }
}
