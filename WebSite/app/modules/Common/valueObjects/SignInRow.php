<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use Override;
use app\helpers\To;

/**
 * Merged Individual + Member row used by the sign-in flow.
 *
 * @phpstan-import-type IndividualShape from IdentityRow
 */
final readonly class SignInRow extends IdentityRow
{
    public function __construct(
        int $id,
        string $email,
        string $firstName,
        ?string $lastName,
        ?string $nickName,
        ?string $avatar,
        public bool $useGravatar,
        public ?string $password,
        public bool $inactivated,
        public ?string $alert,
    ) {
        parent::__construct($id, $email, $firstName, $lastName, $nickName, $avatar);
    }

    /**
     * @param IndividualShape $individual
     * @param object{
     *     Password: string|null,
     *     Inactivated: mixed,
     *     UseGravatar: mixed,
     *     Alert: mixed
     * } $member
     */
    public static function fromRows(object $individual, object $member): self
    {
        return new self(
            id: $individual->Id,
            email: $individual->Email,
            firstName: $individual->FirstName,
            lastName: $individual->LastName,
            nickName: $individual->NickName,
            avatar: $individual->Avatar,
            useGravatar: To::bool($member->UseGravatar),
            password: $member->Password,
            inactivated: To::bool($member->Inactivated),
            alert: is_string($member->Alert) ? $member->Alert : null,
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
