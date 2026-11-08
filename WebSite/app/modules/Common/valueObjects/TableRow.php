<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\helpers\To;

/**
 * One row of a generic data table: the raw cells keyed by column alias, plus the row id.
 */
final readonly class TableRow
{
    /**
     * @param array<string, scalar|null> $cells Column alias => value, as selected by the query
     */
    public function __construct(
        public string $id,
        public array $cells,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var array<string, bool|float|int|string|null> $cells */
        $cells = [];
        foreach (get_object_vars($row) as $name => $value) {
            $cells[(string) $name] = is_scalar($value) ? $value : null;
        }

        return new self(To::str($cells['Id'] ?? null), $cells);
    }
}
