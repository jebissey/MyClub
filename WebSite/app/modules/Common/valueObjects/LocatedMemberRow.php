<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * @phpstan-type LocatedMemberRowShape object{
 *     Id: int|string,
 *     FirstName: string|null,
 *     LastName: string|null,
 *     NickName: string|null,
 *     Avatar: string|null,
 *     UseGravatar: bool|int|string|null,
 *     Email: string,
 *     Location: string|null,
 *     MyPublicDataInPresentationDirectory: string|null,
 *     InPresentationDirectory: int|string,
 *     ShowPhoneInPresentationDirectory: int|string,
 *     ShowEmailInPresentationDirectory: int|string
 * }
 */
final readonly class LocatedMemberRow
{
    public function __construct(
        public int $Id,
        public string $FirstName,
        public string $LastName,
        public ?string $NickName,
        public ?string $Avatar,
        public string $UseGravatar,
        public string $Email,
        public ?string $Location,
        public ?string $MyPublicDataInPresentationDirectory,
        public int $InPresentationDirectory,
        public int $ShowPhoneInPresentationDirectory,
        public int $ShowEmailInPresentationDirectory,
    ) {
    }

    /**
     * @param LocatedMemberRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            FirstName: (string) $row->FirstName,
            LastName: (string) $row->LastName,
            NickName: $row->NickName !== null ? (string) $row->NickName : null,
            Avatar: $row->Avatar !== null ? (string) $row->Avatar : null,
            UseGravatar: (string) $row->UseGravatar,
            Email: (string) $row->Email,
            Location: $row->Location !== null ? (string) $row->Location : null,
            MyPublicDataInPresentationDirectory: $row->MyPublicDataInPresentationDirectory !== null
                ? (string) $row->MyPublicDataInPresentationDirectory
                : null,
            InPresentationDirectory: (int) $row->InPresentationDirectory,
            ShowPhoneInPresentationDirectory: (int) $row->ShowPhoneInPresentationDirectory,
            ShowEmailInPresentationDirectory: (int) $row->ShowEmailInPresentationDirectory,
        );
    }
}
