<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class NeedTypeRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    /**
     * Expects a row shaped as {Id: int|string, Name: string}.
     */
    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: (int) $row->Id,
            name: (string) $row->Name,
        );
    }
}
