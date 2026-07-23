<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * Ligne minimale de la table Contact (Id + TokenCreatedAt uniquement),
 * utilisée pour la vérification d'expiration de token d'invitation.
 *
 * @phpstan-type ContactTokenRowShape object{
 *     Id: int|string,
 *     TokenCreatedAt: string
 * }
 */
final readonly class ContactTokenRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public string $TokenCreatedAt,
    ) {
    }

    /** @param ContactTokenRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            TokenCreatedAt: $row->TokenCreatedAt,
        );
    }
}
