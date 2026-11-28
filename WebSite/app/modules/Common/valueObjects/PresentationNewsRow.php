<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * Strongly typed row returned by the presentation news query.
 *
 * @phpstan-type PresentationNewsRowShape object{
 *     Id: int|string,
 *     Email: string,
 *     FirstName: string,
 *     LastName: string,
 *     PresentationLastUpdate: string
 * }
 */
final readonly class PresentationNewsRow extends AbstractValueObject
{
    public function __construct(
        public int $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $presentationLastUpdate,
    ) {
    }

    /** @param PresentationNewsRowShape $row */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            email: $row->Email,
            firstName: $row->FirstName,
            lastName: $row->LastName,
            presentationLastUpdate: $row->PresentationLastUpdate,
        );
    }
}
