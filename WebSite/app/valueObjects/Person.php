<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * Objet d'identité minimal pour l'utilisateur (ConnectedUser::$person, AuthResult::getUser()).
 * Ne contient que les champs utilisés transversalement (navbar, chat, affichage auteur).
 * Toute donnée spécifique à une vue (compte, annuaire, préférences...) doit être
 * requêtée séparément par le ViewModel/contrôleur concerné.
 *
 * @phpstan-type PersonRow object{
 *    Id: int|string,
 *    Email: string,
 *    Alert?: string|null,
 *    FirstName?: string|null,
 *    LastName?: string|null,
 *    NickName?: string|null,
 *    UseGravatar?: bool|int|string|null,
 *    Avatar?: string|null,
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
        public bool $UseGravatar = false,
        public ?string $Avatar = null,
    ) {
    }

    /**
     * @param PersonRow $row
     */
    public static function fromRow(object $row): self
    {
        $useGravatar = isset($row->UseGravatar) && $row->UseGravatar === 'yes';

        return new self(
            Id: (int) $row->Id,
            Email: $row->Email,
            Alert: $row->Alert ?? null,
            FirstName: $row->FirstName ?? null,
            LastName: $row->LastName ?? null,
            NickName: $row->NickName ?? null,
            UseGravatar: $useGravatar,
            Avatar: $row->Avatar ?? null,
        );
    }
}
