<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\enums\EventAudience;
use app\helpers\To;
use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class EventAudienceRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public EventAudience $audience,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var stdClass $row */
        return new self(
            id: To::requireInt($row->Id ?? null),
            audience: EventAudience::from(
                To::requireStr($row->Audience ?? null),
            ),
        );
    }
}
