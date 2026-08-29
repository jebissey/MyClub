<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class UserAccountViewModel extends LayoutViewModel
{
    /**
     * @param list<string> $emojis
     * @param array<string, string> $i18n
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public bool $readOnly,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $nickName,
        public string $avatar,
        public string $useGravatar,
        public array $emojis,
        public bool $isSelfEdit,
        public array $i18n,
        public string $layout,
        public ?string $alert = null,
        public ?string $memberInfo = null,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/user',
        );
    }
}
