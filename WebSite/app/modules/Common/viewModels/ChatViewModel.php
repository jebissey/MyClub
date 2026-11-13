<?php

declare(strict_types=1);

namespace app\modules\Common\viewModels;

use app\modules\Common\valueObjects\ArticleRow;
use app\modules\Common\valueObjects\ChatMessage;
use app\modules\Common\valueObjects\EventChatRow;
use app\modules\Common\valueObjects\GroupChatRow;
use app\modules\Common\valueObjects\MenuItemWithParentRow;
use app\modules\Common\valueObjects\Person;

final readonly class ChatViewModel extends LayoutViewModel
{
    /**
     * @param list<ChatMessage> $messages
     * @param list<MenuItemWithParentRow> $navItems
     * @param array<string, mixed> $layoutParams
     */
    public function __construct(
        public ?ArticleRow $article,
        public ?EventChatRow $event,
        public ?GroupChatRow $group,
        public array $messages,
        public Person $person,
        public array $navItems,
        public bool $newMessages,
        string $btnParent,
        array $layoutParams = [],
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btnHistoryBack: true,
            btnParent: $btnParent,
            btnPlus: false,
        );
    }
}
