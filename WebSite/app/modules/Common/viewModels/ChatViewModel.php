<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

final readonly class ChatViewModel extends LayoutViewModel
{
    /**
     * @param list<object> $messages
     * @param list<mixed> $navItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public object $article,
        public ?object $event,
        public ?object $group,
        public array $messages,
        public object $person,
        public array $navItems,
        public bool $newMessages,
        string $btnParent,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: $btnParent,
        );
    }
}
