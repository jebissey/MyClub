<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\helpers\To;
use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class UserSupplyRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public int $idNeed,
        public int $supply,
        public string $label,
        public string $name,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: To::requireInt($row->Id ?? null),
            idNeed: To::requireInt($row->IdNeed ?? null),
            supply: To::requireInt($row->Supply ?? null),
            label: To::requireStr($row->Label ?? null),
            name: To::requireStr($row->Name ?? null),
        );
    }
}
