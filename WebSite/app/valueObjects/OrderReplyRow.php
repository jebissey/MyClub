<?php

declare(strict_types=1);

namespace app\valueObjects;

/**
 * Représentation fortement typée d'une réponse à une commande groupée (table OrderReply).
 *
 * @phpstan-type OrderReplyRowShape object{
 *     Id: int|string,
 *     IdPerson: int|string,
 *     IdOrder: int|string,
 *     Answers: string,
 *     LastUpdate: string
 * }
 */
final readonly class OrderReplyRow extends AbstractValueObject
{
    public function __construct(
        public int $Id,
        public int $IdPerson,
        public int $IdOrder,
        public string $Answers,
        public string $LastUpdate,
    ) {
    }

    /** @param OrderReplyRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Id: (int) $row->Id,
            IdPerson: (int) $row->IdPerson,
            IdOrder: (int) $row->IdOrder,
            Answers: $row->Answers,
            LastUpdate: $row->LastUpdate,
        );
    }
}
