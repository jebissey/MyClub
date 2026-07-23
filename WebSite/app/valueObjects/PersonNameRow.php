<?php

declare(strict_types=1);

namespace app\valueObjects;

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
        public string $FirstName,
        public string $LastName,
    ) {
    }

    /** @param PersonNameRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            FirstName: $row->FirstName,
            LastName: $row->LastName,
        );
    }
}
