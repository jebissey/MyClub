<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * @phpstan-type IndividualBasicRowShape object{
 *     Id: int|string,
 *     Email: string,
 *     FirstName: string|null,
 *     LastName: string|null,
 *     NickName: string|null,
 *     Avatar: string|null,
 * }
 */
final readonly class IndividualBasicRow
{
    public function __construct(
        public int $id,
        public string $email,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $nickName,
        public ?string $avatar,
    ) {
    }

    /**
     * @param IndividualBasicRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            email: $row->Email,
            firstName: $row->FirstName,
            lastName: $row->LastName,
            nickName: $row->NickName,
            avatar: $row->Avatar,
        );
    }
}
