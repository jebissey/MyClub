<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

final readonly class EventListFilter
{
    public function __construct(
        public bool $filterByPreferences = false,
        public bool $registeredOnly = false,
        public ?int $eventTypeId = null,
    ) {
    }
}
