<?php

declare(strict_types=1);

namespace app\valueObjects;

use app\enums\EventAudience;

/**
 * Ligne minimale de la table Event (Id + Audience uniquement),
 * utilisée pour les vérifications de droit d'accès/inscription.
 *
 * @phpstan-type EventAudienceRowShape object{
 *     Id: int|string,
 *     Audience: string
 * }
 */
final readonly class EventAudienceRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public EventAudience $Audience,
    ) {
    }

    /** @param EventAudienceRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            Audience: EventAudience::from($row->Audience),
        );
    }
}
