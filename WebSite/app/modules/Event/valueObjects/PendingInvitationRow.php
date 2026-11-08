<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\helpers\To;
use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class PendingInvitationRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public ?string $nickName,
        public string $token,
        public string $invitedAt,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var stdClass $row */
        return new self(
            id: To::requireInt($row->Id ?? null),
            nickName: To::nullableStr($row->NickName ?? null),
            token: To::requireStr($row->Token ?? null),
            invitedAt: To::requireStr($row->InvitedAt ?? null),
        );
    }
}
