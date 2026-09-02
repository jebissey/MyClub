<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;

final readonly class EventChatViewModel extends LayoutViewModel
{
    /**
     * NOTE: $event comes from the generic dataHelper->get('Event', ..., 'CreatedBy, Summary,
     * Id, StartTime, Duration, Location') — a partial column subset with no matching VO in
     * app\valueObjects, so it stays a generic object here.
     *
     * @param list<mixed> $messages No matching VO exists yet for MessageDataHelper::getEventMessages() rows.
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public ?object $article,
        public object $event,
        public ?object $group,
        public array $messages,
        public ?object $person,
        public bool $newMessages,
        int $eventId,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: "/event/{$eventId}",
        );
    }
}
