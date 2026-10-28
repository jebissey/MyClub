<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use Override;
use app\helpers\To;

/**
 * Identity fields plus the Gravatar flag.
 *
 * @phpstan-import-type IndividualShape from IdentityRow
 * @phpstan-type JoinedShape object{
 *     Id: int,
 *     Email: string,
 *     FirstName: string,
 *     LastName: string|null,
 *     NickName: string|null,
 *     Avatar: string|null,
 *     UseGravatar: mixed
 * }
 */
final readonly class MemberBasicRow extends IdentityRow
{
    public function __construct(
        int $id,
        string $email,
        string $firstName,
        ?string $lastName,
        ?string $nickName,
        ?string $avatar,
        public bool $useGravatar,
    ) {
        parent::__construct($id, $email, $firstName, $lastName, $nickName, $avatar);
    }

    /**
     * Hydrates from a single joined Individual + Member SQL row.
     *
     * @param JoinedShape $row
     */
    public static function fromJoinedRow(object $row): self
    {
        return new self(
            id: $row->Id,
            email: $row->Email,
            firstName: $row->FirstName,
            lastName: $row->LastName,
            nickName: $row->NickName,
            avatar: $row->Avatar,
            useGravatar: To::bool($row->UseGravatar),
        );
    }

    #[Override]
    public function toPerson(): Person
    {
        return new Person(
            id: $this->id,
            email: $this->email,
            firstName: $this->firstName,
            lastName: $this->lastName,
            nickName: $this->nickName,
            useGravatar: $this->useGravatar,
            avatar: $this->avatar,
        );
    }
}
