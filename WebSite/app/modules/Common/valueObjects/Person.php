<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * Objet d'identité minimal pour l'utilisateur (ConnectedUser::$person, AuthResult::getUser()).
 * Ne contient que les champs utilisés transversalement (navbar, chat, affichage auteur).
 * Toute donnée spécifique à une vue (compte, annuaire, préférences...) doit être
 * requêtée séparément par le ViewModel/contrôleur concerné.
 *
 * @phpstan-type PersonRow object{
 *     Id: int|string,
 *     Email: string,
 *     Alert?: string|null,
 *     FirstName?: string|null,
 *     LastName?: string|null,
 *     NickName?: string|null,
 *     UseGravatar?: bool|int|string|null,
 *     Avatar?: string|null
 * }
 *
 * @phpstan-type PersonArray array{
 *     Id: int|string,
 *     Email: string,
 *     Alert?: string|null,
 *     FirstName?: string|null,
 *     LastName?: string|null,
 *     NickName?: string|null,
 *     UseGravatar?: bool|int|string|null,
 *     Avatar?: string|null
 * }
 */
final readonly class Person extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public string $email,
        public ?string $alert = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $nickName = null,
        public bool $useGravatar = false,
        public ?string $avatar = null,
    ) {
    }

    /**
     * @param PersonRow $row
     */
    public static function fromRow(object $row): self
    {
        $useGravatar = isset($row->UseGravatar) && $row->UseGravatar === 'yes';

        return new self(
            id: (int) $row->Id,
            email: $row->Email,
            alert: $row->Alert ?? null,
            firstName: $row->FirstName ?? null,
            lastName: $row->LastName ?? null,
            nickName: $row->NickName ?? null,
            useGravatar: $useGravatar,
            avatar: $row->Avatar ?? null,
        );
    }

    /**
     * @param PersonArray $row
     */
    public static function fromArray(array $row): self
    {
        $useGravatar = isset($row['UseGravatar']) && $row['UseGravatar'] === 'yes';

        return new self(
            id: (int) $row['Id'],
            email: $row['Email'],
            alert: $row['Alert'] ?? null,
            firstName: $row['FirstName'] ?? null,
            lastName: $row['LastName'] ?? null,
            nickName: $row['NickName'] ?? null,
            useGravatar: $useGravatar,
            avatar: $row['Avatar'] ?? null,
        );
    }
}
