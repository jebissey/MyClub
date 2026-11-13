<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use app\helpers\To;
use stdClass;

/**
 * Contact details of an active member, as shown when copying email addresses.
 */
final readonly class MemberContactRow
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $nickName,
        public string $phone,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            firstName: To::str($row->FirstName),
            lastName: To::str($row->LastName),
            nickName: To::str($row->NickName),
            phone: To::str($row->Phone),
        );
    }
}
