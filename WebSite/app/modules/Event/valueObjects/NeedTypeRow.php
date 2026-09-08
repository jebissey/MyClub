<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class NeedTypeRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public string $Name,
    ) {
    }

    /**
     * Expects a row shaped as {Id: int|string, Name: string}.
     */
    public static function fromStdClass(\stdClass $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Name: (string) $row->Name,
        );
    }
}
