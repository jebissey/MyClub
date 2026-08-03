<?php

declare(strict_types=1);

namespace app\valueObjects;

use stdClass;

/**
 * @phpstan-type MemberGroupRowShape object{
 *     Id: int|string,
 *     PersonId: int|string,
 *     FirstName: string,
 *     LastName: string,
 *     Email: string,
 *     Preferences: string|null,
 *     Availabilities: string|null,
 *     InPresentationDirectory: int|string,
 *     ShowPhoneInPresentationDirectory: int|string,
 *     ShowEmailInPresentationDirectory: int|string
 * }
 */
final readonly class MemberGroupRow
{
    public function __construct(
        public int $Id,
        public int $PersonId,
        public string $FirstName,
        public string $LastName,
        public string $Email,
        public ?string $Preferences,
        public ?string $Availabilities,
        public int $InPresentationDirectory,
        public int $ShowPhoneInPresentationDirectory,
        public int $ShowEmailInPresentationDirectory,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            Id: (int) $row->Id,
            PersonId: (int) $row->PersonId,
            FirstName: $row->FirstName,
            LastName: $row->LastName,
            Email: $row->Email,
            Preferences: $row->Preferences,
            Availabilities: $row->Availabilities,
            InPresentationDirectory: (int) $row->InPresentationDirectory,
            ShowPhoneInPresentationDirectory: (int) $row->ShowPhoneInPresentationDirectory,
            ShowEmailInPresentationDirectory: (int) $row->ShowEmailInPresentationDirectory,
        );
    }

    /**
     * Pont temporaire pour les consommateurs qui attendent encore un stdClass
     * (ex: Person::fromRow via un cast PersonRow).
     */
    public function toStdClass(): stdClass
    {
        $obj = new stdClass();
        foreach (get_object_vars($this) as $key => $value) {
            $obj->$key = $value;
        }
        return $obj;
    }
}
