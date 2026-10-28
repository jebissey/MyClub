<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;
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
        public int $id,
        public EventAudience $audience,
    ) {
    }

    /** @param EventAudienceRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            audience: EventAudience::from($row->Audience),
        );
    }
}
