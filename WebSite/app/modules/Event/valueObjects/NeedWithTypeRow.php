<?php

declare(strict_types=1);

namespace app\modules\Event\valueObjects;

use app\helpers\To;

/**
 * A need together with the name of its type (NeedType may be missing: LEFT JOIN).
 *
 * @phpstan-type NeedWithTypeRowShape object{
 *     Id: int|string,
 *     IdNeedType: int|string|null,
 *     Label: string,
 *     Name: string,
 *     ParticipantDependent: int|string|null,
 *     TypeName: string|null
 * }
 */
final readonly class NeedWithTypeRow
{
    public function __construct(
        public int $id,
        public int $idNeedType,
        public string $label,
        public string $name,
        public bool $participantDependent,
        public ?string $typeName,
    ) {
    }

    public static function fromStdClass(object $row): self
    {
        /** @var NeedWithTypeRowShape $row */
        return new self(
            id: To::int($row->Id),
            idNeedType: To::int($row->IdNeedType),
            label: To::str($row->Label),
            name: To::str($row->Name),
            participantDependent: To::bool($row->ParticipantDependent),
            typeName: To::nullableStr($row->TypeName),
        );
    }
}
