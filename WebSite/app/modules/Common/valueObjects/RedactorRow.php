<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\helpers\To;

/**
 * An active member holding the redactor authorization.
 *
 * @phpstan-type RedactorRowShape object{
 *     PersonId: int|string,
 *     FirstName: string|null,
 *     LastName: string|null,
 *     NickName: string|null,
 *     Email: string
 * }
 */
final readonly class RedactorRow extends AbstractValueObject
{
    public function __construct(
        public int $personId,
        public string $firstName,
        public string $lastName,
        public ?string $nickName,
        public string $email,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var RedactorRowShape $row */
        return new self(
            personId: To::int($row->PersonId),
            firstName: To::str($row->FirstName),
            lastName: To::str($row->LastName),
            nickName: To::nullableStr($row->NickName),
            email: To::str($row->Email),
        );
    }
}
