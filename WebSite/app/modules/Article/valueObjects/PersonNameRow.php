<?php

declare(strict_types=1);

namespace app\modules\Article\valueObjects;

use app\modules\Common\valueObjects\AbstractValueObject;

/**
 * Ligne minimale de la table Person (FirstName + LastName uniquement).
 *
 * @phpstan-type PersonNameRowShape object{
 *     FirstName: string,
 *     LastName: string
 * }
 */
final readonly class PersonNameRow extends AbstractValueObject
{
    public function __construct(
        public string $firstName,
        public string $lastName,
    ) {
    }

    /** @param PersonNameRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            firstName: $row->FirstName,
            lastName: $row->LastName,
        );
    }
}
