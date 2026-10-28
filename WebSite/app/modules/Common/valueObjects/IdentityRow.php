<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * Identity fields coming from the Individual table.
 *
 * @phpstan-type IndividualShape object{
 *     Id: int,
 *     Email: string,
 *     FirstName: string,
 *     LastName: string|null,
 *     NickName: string|null,
 *     Avatar: string|null
 * }
 */
readonly class IdentityRow
{
    public function __construct(
        public int $id,
        public string $email,
        public string $firstName,
        public ?string $lastName,
        public ?string $nickName,
        public ?string $avatar,
    ) {
    }

    /**
     * @param IndividualShape $individual
     */
    public static function fromIndividual(object $individual): self
    {
        return new self(
            id: $individual->Id,
            email: $individual->Email,
            firstName: $individual->FirstName,
            lastName: $individual->LastName,
            nickName: $individual->NickName,
            avatar: $individual->Avatar,
        );
    }

    public function toPerson(): Person
    {
        return new Person(
            id: $this->id,
            email: $this->email,
            firstName: $this->firstName,
            lastName: $this->lastName,
            nickName: $this->nickName,
            avatar: $this->avatar,
        );
    }
}
