<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use stdClass;
use app\helpers\To;
use app\modules\Common\valueObjects\AbstractValueObject;

final readonly class EventParticipantRow extends AbstractValueObject
{
    public function __construct(
        public ?string $email,
        public ?string $nickName,
        public ?string $firstName,
        public ?string $lastName,
        public ?int $personId,
        public ?int $inPresentationDirectory,
        public ?int $contactId,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            email: To::nullableStr($row->Email ?? null),
            nickName: To::nullableStr($row->NickName ?? null),
            firstName: To::nullableStr($row->FirstName ?? null),
            lastName: To::nullableStr($row->LastName ?? null),
            personId: To::nullableInt($row->PersonId ?? null),
            inPresentationDirectory: To::nullableInt(
                $row->InPresentationDirectory ?? null,
            ),
            contactId: To::nullableInt($row->ContactId ?? null),
        );
    }
}
