<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\helpers\To;
use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class InvitationRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public string $email,
        public ?string $nickName,
        public int $idEvent,
        public int $invitedBy,
        public string $invitedAt,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var stdClass $row */
        return new self(
            id: To::requireInt($row->Id ?? null),
            email: To::requireStr($row->Email ?? null),
            nickName: To::nullableStr($row->NickName ?? null),
            idEvent: To::requireInt($row->IdEvent ?? null),
            invitedBy: To::requireInt($row->InvitedBy ?? null),
            invitedAt: To::requireStr($row->InvitedAt ?? null),
        );
    }
}
