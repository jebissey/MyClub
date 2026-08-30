<?php

declare(strict_types=1);

namespace app\modules\PersonManager\viewModels;

use stdClass;
use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\MenuItemRow;
use app\valueObjects\Person;

final readonly class GroupChatViewModel extends LayoutViewModel
{
    /**
     * @param mixed $messages
     * @param list<MenuItemRow> $navItems
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public ?stdClass $article,
        public ?stdClass $event,
        public object $group,
        public mixed $messages,
        public Person $person,
        public array $navItems,
        public string $btnParent,
        public bool $newMessages,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: $btnParent,
        );
    }
}
