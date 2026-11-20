<?php

declare(strict_types=1);

namespace app\models;

use stdClass;
use app\helpers\Application;
use app\modules\Common\valueObjects\EventAttributeRow;
use app\modules\Common\valueObjects\EventTypeRow;
use app\modules\Event\valueObjects\NeedTypeRow;

/**
 * Reference data used to filter and display events (types, needs, attributes).
 */
final class EventLookupDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /** @return list<EventTypeRow> Active event types only. */
    public function getActiveEventTypes(): array
    {
        return array_map(
            static fn(stdClass $row): EventTypeRow => EventTypeRow::fromStdClass($row),
            array_values($this->gets('EventType', ['Inactivated' => 0], 'Id, Name, Inactivated, IdGroup'))
        );
    }

    /** @return list<NeedTypeRow> */
    public function getNeedTypes(): array
    {
        return array_map(
            static fn(stdClass $row): NeedTypeRow => NeedTypeRow::fromStdClass($row),
            array_values($this->gets('NeedType', [], 'Id, Name'))
        );
    }

    /** @return list<EventAttributeRow> All attributes that can be attached to an event. */
    public function getAllAttributes(): array
    {
        return array_map(
            static fn(stdClass $row): EventAttributeRow => EventAttributeRow::fromStdClass($row),
            array_values($this->gets('Attribute', [], 'Id, Name, Detail, Color'))
        );
    }
}
