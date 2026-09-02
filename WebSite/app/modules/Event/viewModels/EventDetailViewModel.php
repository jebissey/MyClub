<?php

declare(strict_types=1);

namespace app\modules\Event\viewModels;

use app\modules\Common\viewModels\LayoutViewModel;
use app\valueObjects\EventAttributeRow;
use app\valueObjects\EventDetailRow;

final readonly class EventDetailViewModel extends LayoutViewModel
{
    /**
     * NOTE: $userEmail is intentionally NOT redeclared here — LayoutViewModel already
     * owns that property (populated from layoutParams by baseArgsFrom), and re-declaring
     * it in a child promoted constructor causes a "readonly property already assigned"
     * error since both assignments target the same underlying property.
     *
     * NOTE: participants stays untyped — ParticipantDataHelper::getEventParticipants()
     * returns raw rows with Email/NickName/FirstName/LastName/PersonId/
     * InPresentationDirectory/ContactId, while app\valueObjects\EventParticipant only
     * models PersonId+Email (and drops rows with no Email). Mapping through it here
     * would silently discard fields the template likely needs for display.
     *
     * @param list<EventAttributeRow> $attributes
     * @param list<mixed> $participants
     * @param list<mixed> $eventNeeds No matching VO exists yet for this Need/EventNeed aggregate shape.
     * @param list<mixed> $participantSupplies No matching VO exists yet for this shape.
     * @param list<mixed> $userSupplies No matching VO exists yet for this shape.
     * @param array<string, mixed> $layoutParams Full output of Params::getAll().
     */
    public function __construct(
        public int $eventId,
        public EventDetailRow $event,
        public array $attributes,
        public array $participants,
        public bool $isRegistered,
        public int $countOfMessages,
        public array $eventNeeds,
        public array $participantSupplies,
        public array $userSupplies,
        public string|false $token,
        public string $message,
        public string $messageType,
        array $layoutParams = []
    ) {
        parent::__construct(
            ...self::baseArgsFrom($layoutParams),
            btn_HistoryBack: true,
            btn_Parent: '/nextEvents',
        );
    }
}
