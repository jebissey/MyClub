<?php

declare(strict_types=1);

namespace app\modules\PersonManager\valueObjects;

use app\helpers\To;

/**
 * The editable fields of a group.
 */
final readonly class GroupEditRow
{
    public function __construct(
        public string $name,
        public bool $selfRegistration,
    ) {
    }

    /**
     * @param object{Name: mixed, SelfRegistration: mixed} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            name: To::str($row->Name),
            selfRegistration: To::int($row->SelfRegistration) !== 0,
        );
    }
}
