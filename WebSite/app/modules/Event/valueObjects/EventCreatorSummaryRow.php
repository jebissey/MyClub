<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

/**
 * @phpstan-type EventCreatorSummaryRowObject object{
 *     CreatedBy: int|string,
 *     Summary: string
 * }
 */
final readonly class EventCreatorSummaryRow
{
    public function __construct(
        public int $createdBy,
        public string $summary,
    ) {
    }

    /**
     * @param EventCreatorSummaryRowObject $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            createdBy: (int) $row->CreatedBy,
            summary: (string) $row->Summary,
        );
    }
}
