<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * @phpstan-type EventCreatorSummaryRowObject object{
 *     CreatedBy: int|string,
 *     Summary: string
 * }
 */
final readonly class EventCreatorSummaryRow
{
    public function __construct(
        public int $CreatedBy,
        public string $Summary,
    ) {
    }

    /**
     * @param EventCreatorSummaryRowObject $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            CreatedBy: (int) $row->CreatedBy,
            Summary: (string) $row->Summary,
        );
    }
}
