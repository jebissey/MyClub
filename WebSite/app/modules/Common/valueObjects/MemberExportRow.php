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
        public int $id,
        public bool $inactivated,
        public string $email,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $phone,
        public ?string $customFields,
    ) {
    }

    /**
     * @param MemberExportRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            inactivated: (bool) $row->Inactivated,
            email: (string) $row->Email,
            firstName: $row->FirstName !== null ? (string) $row->FirstName : null,
            lastName: $row->LastName !== null ? (string) $row->LastName : null,
            phone: $row->Phone !== null ? (string) $row->Phone : null,
            customFields: $row->CustomFields !== null ? (string) $row->CustomFields : null,
        );
    }
}
