<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

/**
 * @phpstan-type PendingDesignRowShape object{
 *     PersonId: int|string,
 *     Email: string,
 *     DesignId: int|string,
 *     DesignName: string,
 *     DesignDetail: string|null
 * }
 */
final readonly class PendingDesignRow
{
    public function __construct(
        public int $personId,
        public string $email,
        public int $designId,
        public string $designName,
        public string $designDetail,
    ) {
    }

    /**
     * @param PendingDesignRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            personId: (int) $row->PersonId,
            email: (string) $row->Email,
            designId: (int) $row->DesignId,
            designName: (string) $row->DesignName,
            designDetail: (string) ($row->DesignDetail ?? ''),
        );
    }
}
