<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\helpers\To;

/**
 * Merged Individual + Member row used by the sign-in flow.
 *
 * @phpstan-import-type IndividualShape from IdentityRow
 */
final readonly class SignInRow extends IdentityRow
{
    public function __construct(
        int $Id,
        string $Email,
        string $FirstName,
        ?string $LastName,
        ?string $NickName,
        ?string $Avatar,
        public bool $UseGravatar,
        public ?string $Password,
        public bool $Inactivated,
        public ?string $Alert,
    ) {
        parent::__construct($Id, $Email, $FirstName, $LastName, $NickName, $Avatar);
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
            Id: $individual->Id,
            Email: $individual->Email,
            FirstName: $individual->FirstName,
            LastName: $individual->LastName,
            NickName: $individual->NickName,
            Avatar: $individual->Avatar,
            UseGravatar: To::bool($member->UseGravatar),
            Password: $member->Password,
            Inactivated: To::bool($member->Inactivated),
            Alert: is_string($member->Alert) ? $member->Alert : null,
        );
    }

    #[\Override]
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
