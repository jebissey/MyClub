<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\helpers\To;

/**
 * Minimal reference row (id + display name), e.g. a group or an event type in a select list.
 *
 * @phpstan-type IdNameRowShape object{Id: int|string, Name: string}
 */
final readonly class IdNameRow
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var IdNameRowShape $row */
        return new self(To::int($row->Id), To::str($row->Name));
    }
}
