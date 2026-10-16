<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * @phpstan-type MemberExportRowShape object{
 *     Id: int|string,
 *     Inactivated: int|bool|string|null,
 *     Email: string,
 *     FirstName: string|null,
 *     LastName: string|null,
 *     Phone: string|null,
 *     CustomFields: string|null,
 * }
 */
final readonly class MemberExportRow
{
    public function __construct(
        public int $Id,
        public bool $Inactivated,
        public string $Email,
        public ?string $FirstName,
        public ?string $LastName,
        public ?string $Phone,
        public ?string $CustomFields,
    ) {
    }

    /**
     * @param MemberExportRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Inactivated: (bool) $row->Inactivated,
            Email: (string) $row->Email,
            FirstName: $row->FirstName !== null ? (string) $row->FirstName : null,
            LastName: $row->LastName !== null ? (string) $row->LastName : null,
            Phone: $row->Phone !== null ? (string) $row->Phone : null,
            CustomFields: $row->CustomFields !== null ? (string) $row->CustomFields : null,
        );
    }
}
