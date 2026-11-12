<?php

declare(strict_types=1);

namespace app\modules\Membership\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class MembershipRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public int $personId,
        public string $season,
        public string $status,
        public int $amountCents,
        public ?string $paidAt,
    ) {}

    public function paidAtForDisplay(): string
    {
        return $this->paidAt ?? '';
    }
}
