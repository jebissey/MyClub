<?php

declare(strict_types=1);

namespace app\modules\PersonManager\valueObjects;

use app\helpers\To;

/**
 * A group as listed on the groups page, with its authorizations as display text.
 */
final readonly class GroupRow
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $selfRegistration,
        public string $authorizations,
    ) {
    }

    /**
     * @param object{Id: mixed, Name: mixed, SelfRegistration: mixed, Authorizations: mixed} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: To::int($row->Id),
            name: To::str($row->Name),
            selfRegistration: To::int($row->SelfRegistration) !== 0,
            authorizations: To::str($row->Authorizations),
        );
    }
}
