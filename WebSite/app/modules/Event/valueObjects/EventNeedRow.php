<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\helpers\To;
use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class EventNeedRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public string $label,
        public string $name,
        public int $participantDependent,
        public int $requiredQuantity,
        public int $providedQuantity,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: To::requireInt($row->Id ?? null),
            label: To::requireStr($row->Label ?? null),
            name: To::requireStr($row->Name ?? null),
            participantDependent: To::requireInt($row->ParticipantDependent ?? null),
            requiredQuantity: To::requireInt($row->RequiredQuantity ?? null),
            providedQuantity: To::requireInt($row->ProvidedQuantity ?? null),
        );
    }
}
