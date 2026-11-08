<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\helpers\To;

/**
 * One row of the event types table (see TableControllerDataHelper::getEventTypesQuery()).
 *
 * @phpstan-type EventTypeListRowShape object{Id: int|string, EventTypeName: string, GroupName: string|null, Attributes: string|null}
 */
final readonly class EventTypeListRow
{
    public function __construct(
        public int $id,
        public string $eventTypeName,
        public ?string $groupName,
        public ?string $attributes,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var EventTypeListRowShape $row */
        return new self(
            id: To::int($row->Id),
            eventTypeName: To::str($row->EventTypeName),
            groupName: To::nullableStr($row->GroupName),
            attributes: To::nullableStr($row->Attributes),
        );
    }
}
