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
        int $Id,
        string $Email,
        string $FirstName,
        ?string $LastName,
        ?string $NickName,
        ?string $Avatar,
        public bool $UseGravatar,
    ) {
        parent::__construct($Id, $Email, $FirstName, $LastName, $NickName, $Avatar);
    }

    /**
     * Hydrates from a single joined Individual + Member SQL row.
     *
     * @param JoinedShape $row
     */
    public static function fromJoinedRow(object $row): self
    {
        return new self(
            Id: $row->Id,
            Email: $row->Email,
            FirstName: $row->FirstName,
            LastName: $row->LastName,
            NickName: $row->NickName,
            Avatar: $row->Avatar,
            UseGravatar: To::bool($row->UseGravatar),
        );
    }

    #[Override]
    public function toPerson(): Person
    {
        return new Person(
            Id: $this->Id,
            Email: $this->Email,
            FirstName: $this->FirstName,
            LastName: $this->LastName,
            NickName: $this->NickName,
            UseGravatar: $this->UseGravatar,
            Avatar: $this->Avatar,
        );
    }
}
