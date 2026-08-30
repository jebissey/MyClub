<?php

declare(strict_types=1);

namespace app\modules\User\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\EventTypeRow;

final readonly class UserPreferencesViewModel extends LayoutViewModel
{
    /**
     * @param mixed $currentPreferences
     * @param list<EventTypeRow> $eventTypes
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public mixed $currentPreferences,
        public array $eventTypes,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/user',
        );
    }
}
