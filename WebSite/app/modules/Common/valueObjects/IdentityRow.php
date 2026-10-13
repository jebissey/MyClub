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
        public int $Id,
        public string $Email,
        public string $FirstName,
        public ?string $LastName,
        public ?string $NickName,
        public ?string $Avatar,
    ) {
    }

    /**
     * @param IndividualShape $individual
     */
    public static function fromIndividual(object $individual): self
    {
        return new self(
            Id: $individual->Id,
            Email: $individual->Email,
            FirstName: $individual->FirstName,
            LastName: $individual->LastName,
            NickName: $individual->NickName,
            Avatar: $individual->Avatar,
        );
    }

    public function toPerson(): Person
    {
        return new Person(
            Id: $this->Id,
            Email: $this->Email,
            FirstName: $this->FirstName,
            LastName: $this->LastName,
            NickName: $this->NickName,
            Avatar: $this->Avatar,
        );
    }
}
