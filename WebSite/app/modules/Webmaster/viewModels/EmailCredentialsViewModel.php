<?php

declare(strict_types=1);

namespace app\modules\Webmaster\viewModels;

use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\viewModels\LayoutViewModel;

final readonly class EmailCredentialsViewModel extends LayoutViewModel
{
    /**
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, string> $error Validation messages indexed by field name
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public array $navItems,
        public ?string $sendMethod,
        public ?string $smtpAccount,
        public ?string $smtpFrom,
        public ?string $smtpHost,
        public ?string $smtpPort,
        public ?string $smtpEncryption,
        public ?string $mailjetApiKey,
        public ?string $mailjetSender,
        public ?string $brevoApiKey,
        public ?string $brevoSender,
        public ?string $dailyLimit,
        public ?string $monthlyLimit,
        public array $error = [],
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: '/webmaster',
            btnPlus: false,
        );
    }
}
