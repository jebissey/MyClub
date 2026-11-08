<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\helpers\To;
use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class IndividualRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public string $type,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var stdClass $row */
        return new self(
            id: To::requireInt($row->Id ?? null),
            type: To::requireStr($row->Type ?? null),
        );
    }
}
