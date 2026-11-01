<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\helpers\To;

final readonly class GroupInfo
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    public static function fromStdClass(object $row): ?self
    {
        if (!isset($row->IdGroup) || $row->IdGroup == null || !isset($row->GroupName)) {
            return null;
        }
        return new self(
            id: To::int($row->IdGroup),
            name: To::str($row->GroupName),
        );
    }
}
