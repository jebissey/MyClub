<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * @phpstan-type DirectoryPersonRowShape object{
 *     Id: int|string,
 *     FirstName: string,
 *     LastName: string|null,
 *     NickName: string|null,
 *     Avatar: string|null,
 *     UseGravatar: string,
 *     Email: string
 * }
 */
final readonly class DirectoryPersonRow
{
    public function __construct(
        public int $id,
        public string $firstName,
        public ?string $lastName,
        public ?string $nickName,
        public ?string $avatar,
        public string $useGravatar,
        public string $email,
        public string $userImg,
    ) {
    }

    /**
     * @param DirectoryPersonRowShape $row
     */
    public static function fromStdClass(object $row, string $userImg): self
    {
        return new self(
            id: (int) $row->Id,
            firstName: (string) $row->FirstName,
            lastName: $row->LastName !== null ? (string) $row->LastName : null,
            nickName: $row->NickName !== null ? (string) $row->NickName : null,
            avatar: $row->Avatar !== null ? (string) $row->Avatar : null,
            useGravatar: (string) $row->UseGravatar,
            email: (string) $row->Email,
            userImg: $userImg,
        );
    }
}
