<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\helpers\To;
use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class ParticipantSupplyRow extends AbstractValueObject
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $nickName,
        public string $needLabel,
        public string $needName,
        public int $supply,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            firstName: To::nullableStr($row->FirstName ?? null),
            lastName: To::nullableStr($row->LastName ?? null),
            nickName: To::nullableStr($row->NickName ?? null),
            needLabel: To::requireStr($row->NeedLabel ?? null),
            needName: To::requireStr($row->NeedName ?? null),
            supply: To::requireInt($row->Supply ?? null),
        );
    }
}
