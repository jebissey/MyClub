<?php

declare(strict_types=1);

namespace app\valueObjects;

use app\helpers\GravatarHandler;
use app\helpers\WebApp;

/**
 * Objet d'identité pour l'utilisateur (ConnectedUser::$person, AuthResult::getUser()).
 *
 * @phpstan-type PersonRow object{
 *    Id: int|string,
 *    Email: string,
 *    Alert?: string|null,
 *    FirstName?: string|null,
 *    LastName?: string|null,
 *    NickName?: string|null,
 *    Password?: string|null,
 *    Inactivated?: bool|int|string|null,
 *    LastSignIn?: string|null,
 *    LastSignOut?: string|null,
 *    TokenCreatedAt?: string|null,
 *    Imported?: bool|int|string|null,
 *    MemberInfo?: string|null,
 *    UseGravatar?: bool|int|string|null,
 *    Avatar?: string|null,
 *    InPresentationDirectory?: bool|int|string|null,
 *    ShowPhoneInPresentationDirectory?: bool|int|string|null,
 *    ShowEmailInPresentationDirectory?: bool|int|string|null,
 *    MyPublicDataInPresentationDirectory?: string|null,
 *    Preferences?: string|null,
 *    Availabilities?: string|null,
 *    Notifications?: string|null,
 *    Notepad?: string|null,
 *    Location?: string|null
 * }
 */
final readonly class Person extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public string $Email,
        public ?string $Alert = null,
        public ?string $FirstName = null,
        public ?string $LastName = null,
        public ?string $NickName = null,
        public ?string $Password = null,
        public bool $Inactivated = false,
        public ?string $LastSignIn = null,
        public ?string $LastSignOut = null,
        public ?string $TokenCreatedAt = null,
        public bool $Imported = false,
        public ?string $MemberInfo = null,
        public bool $UseGravatar = false,
        public ?string $Avatar = null,
        public bool $InPresentationDirectory = false,
        public bool $ShowPhoneInPresentationDirectory = false,
        public bool $ShowEmailInPresentationDirectory = false,
        public ?string $MyPublicDataInPresentationDirectory = null,
        public ?string $Preferences = null,
        public ?string $Availabilities = null,
        public ?string $Notifications = null,
        public ?string $Notepad = null,
        public ?string $Location = null,
        public ?string $UserImg = null,
    ) {
    }

    /**
     * @param PersonRow $row
     */
    public static function fromRow(object $row): self
    {
        $inactivated = (bool)($row->Inactivated ?? false);
        $imported = (bool)($row->Imported ?? false);
        $useGravatar = (bool)($row->UseGravatar ?? false);

        $inPresentationDirectory = (bool)($row->InPresentationDirectory ?? false);
        $showPhone = (bool)($row->ShowPhoneInPresentationDirectory ?? false);
        $showEmail = (bool)($row->ShowEmailInPresentationDirectory ?? false);

        $avatar = $row->Avatar ?? null;
        $email = $row->Email;

        return new self(
            Id: (int)$row->Id,
            Email: $email,
            Alert: $row->Alert ?? null,
            FirstName: $row->FirstName ?? null,
            LastName: $row->LastName ?? null,
            NickName: $row->NickName ?? null,
            Password: $row->Password ?? null,
            Inactivated: $inactivated,
            LastSignIn: $row->LastSignIn ?? null,
            LastSignOut: $row->LastSignOut ?? null,
            TokenCreatedAt: $row->TokenCreatedAt ?? null,
            Imported: $imported,
            MemberInfo: $row->MemberInfo ?? null,
            UseGravatar: $useGravatar,
            Avatar: $avatar,
            InPresentationDirectory: $inPresentationDirectory,
            ShowPhoneInPresentationDirectory: $showPhone,
            ShowEmailInPresentationDirectory: $showEmail,
            MyPublicDataInPresentationDirectory: $row->MyPublicDataInPresentationDirectory ?? null,
            Preferences: $row->Preferences ?? null,
            Availabilities: $row->Availabilities ?? null,
            Notifications: $row->Notifications ?? null,
            Notepad: $row->Notepad ?? null,
            Location: $row->Location ?? null,
            UserImg: WebApp::computeUserImg(
                $useGravatar,
                $email,
                $avatar,
                new GravatarHandler()
            ),
        );
    }
}
