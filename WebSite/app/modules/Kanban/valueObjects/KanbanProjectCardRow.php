<?php

declare(strict_types=1);

namespace app\modules\Kanban\valueObjects;

/**
 * @phpstan-type KanbanProjectCardRowShape object{
 *     Id: int|string,
 *     Title: string,
 *     Detail: string,
 *     Label: string,
 * }
 */
final readonly class KanbanProjectCardRow
{
    public function __construct(
        public int $id,
        public string $title,
        public string $detail,
        public string $label,
    ) {
    }

    /**
     * @param KanbanProjectCardRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            title: $row->Title,
            detail: $row->Detail,
            label: $row->Label,
        );
    }
}
