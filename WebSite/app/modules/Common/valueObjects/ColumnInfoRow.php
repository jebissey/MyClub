<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * Strongly typed row returned by SQLite's PRAGMA table_info.
 *
 * @phpstan-type ColumnInfoRowShape object{
 *     name: string,
 *     type: string,
 *     notnull: int|string,
 *     dflt_value: mixed,
 *     pk: int|string
 * }
 */
final readonly class ColumnInfoRow extends AbstractValueObject
{
    public function __construct(
        public string $name,
        public string $type,
        public int $notnull,
        public mixed $defaultValue,
        public int $pk,
    ) {
    }

    /** @param ColumnInfoRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            name: $row->name,
            type: $row->type,
            notnull: (int) $row->notnull,
            defaultValue: $row->dflt_value,
            pk: (int) $row->pk,
        );
    }
}
