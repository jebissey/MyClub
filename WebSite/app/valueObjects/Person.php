<?php

declare(strict_types=1);

namespace app\valueObjects;

use app\helpers\GravatarHandler;
use app\helpers\WebApp;
use app\valueObjects\AbstractValueObject;

/**
 * Objet d'identité pour l'utilisateur (ConnectedUser::$person, AuthResult::getUser()).
 * Seuls les champs effectivement consommés dans le code sont déclarés ;
 * PHPStan signalera tout accès à un champ non déclaré au fil des corrections
 * de niveaux 7/8/9 — c'est volontaire, pas un oubli.
 *
 * @phpstan-type PersonRow object{
 *    Id: int|string,
 *    Email?: string|null,
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
 *    UseGravatar?: bool|null,
 *    Avatar?: string|null,
 *    InPresentationDirectory: int,
 *    ShowPhoneInPresentationDirectory: int|string,
 *    ShowEmailInPresentationDirectory: int|string,
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
        public ?string $Email = null,
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
        return new self(
            Id: (int)$row->Id,
            Email: $row->Email ?? null,
            Alert: $row->Alert ?? null,
            FirstName: $row->FirstName ?? null,
            LastName: $row->LastName ?? null,
            NickName: $row->NickName ?? null,
            Password: $row->Password ?? null,
            Inactivated: (bool)($row->Inactivated ?? false),
            LastSignIn: $row->LastSignIn ?? null,
            LastSignOut: $row->LastSignOut ?? null,
            TokenCreatedAt: $row->TokenCreatedAt ?? null,
            Imported: (bool)($row->Imported ?? false),
            MemberInfo: $row->MemberInfo ?? null,
            UseGravatar: (bool)($row->UseGravatar ?? false),
            Avatar: $row->Avatar ?? null,
            InPresentationDirectory: (bool)($row->InPresentationDirectory == 1),
            ShowPhoneInPresentationDirectory: (bool)($row->ShowPhoneInPresentationDirectory == 1),
            ShowEmailInPresentationDirectory: (bool)($row->ShowEmailInPresentationDirectory == 1),
            MyPublicDataInPresentationDirectory: $row->MyPublicDataInPresentationDirectory ?? null,
            Preferences: $row->Preferences ?? null,
            Availabilities: $row->Availabilities ?? null,
            Notifications: $row->Notifications ?? null,
            Notepad: $row->Notepad ?? null,
            Location: $row->Location ?? null,
            UserImg: WebApp::computeUserImg($row->UseGravatar ?? false, $row->Email ?? null, $row->Avatar ?? null, new GravatarHandler()),
        );
    }
}
