<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\helpers\To;
use stdClass;

final readonly class GroupChatRow
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    public static function fromRow(object $row): self
    {
        /** @var stdClass $row */
        return new self(
            id: To::requireInt($row->Id ?? null),
            name: To::requireStr($row->Name ?? null),
        );
    }
}
