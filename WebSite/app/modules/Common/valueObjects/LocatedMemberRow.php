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
        public int $id,
        public string $firstName,
        public string $lastName,
        public ?string $nickName,
        public ?string $avatar,
        public string $useGravatar,
        public string $email,
        public ?string $location,
        public ?string $myPublicDataInPresentationDirectory,
        public int $inPresentationDirectory,
        public int $showPhoneInPresentationDirectory,
        public int $showEmailInPresentationDirectory,
        public string $userImg = '',
    ) {
    }

    /**
     * Returns a copy of this row with the computed user image set.
     */
    public function withUserImg(string $userImg): self
    {
        return new self(
            $this->id,
            $this->firstName,
            $this->lastName,
            $this->nickName,
            $this->avatar,
            $this->useGravatar,
            $this->email,
            $this->location,
            $this->myPublicDataInPresentationDirectory,
            $this->inPresentationDirectory,
            $this->showPhoneInPresentationDirectory,
            $this->showEmailInPresentationDirectory,
            $userImg,
        );
    }

    /**
     * @param LocatedMemberRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            firstName: (string) $row->FirstName,
            lastName: (string) $row->LastName,
            nickName: $row->NickName !== null ? (string) $row->NickName : null,
            avatar: $row->Avatar !== null ? (string) $row->Avatar : null,
            useGravatar: (string) $row->UseGravatar,
            email: (string) $row->Email,
            location: $row->Location !== null ? (string) $row->Location : null,
            myPublicDataInPresentationDirectory: $row->MyPublicDataInPresentationDirectory !== null
                ? (string) $row->MyPublicDataInPresentationDirectory
                : null,
            inPresentationDirectory: (int) $row->InPresentationDirectory,
            showPhoneInPresentationDirectory: (int) $row->ShowPhoneInPresentationDirectory,
            showEmailInPresentationDirectory: (int) $row->ShowEmailInPresentationDirectory,
        );
    }
}
