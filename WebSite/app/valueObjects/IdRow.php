<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * Ligne minimale ne contenant qu'un identifiant, utilisée pour les
 * vérifications d'existence ou récupérations d'Id sur SELECT Id.
 * Réutilisable quelle que soit la table d'origine.
 *
 * @phpstan-type IdRowShape object{Id: int|string}
 */
final readonly class IdRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
    ) {
    }

    /** @param IdRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
        );
    }
}
