<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

/**
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
        public int $id,
        public int $idPerson,
        public int $idOrder,
        public string $answers,
        public string $lastUpdate,
    ) {
    }

    /** @param OrderReplyRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            idPerson: (int) $row->IdPerson,
            idOrder: (int) $row->IdOrder,
            answers: $row->Answers,
            lastUpdate: $row->LastUpdate,
        );
    }
}
