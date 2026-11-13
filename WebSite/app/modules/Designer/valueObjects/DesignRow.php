<?php

declare(strict_types=1);

namespace app\modules\Designer\valueObjects;

use app\helpers\To;
use stdClass;

final readonly class DesignRow
{
    public function __construct(
        public int $id,
        public string $name,
        public string $detail,
        public string $navBar,
        public string $status,
        public int $onlyForMembers,
        public ?int $idGroup,
        public string $nameOfDesigner,
        public string $votes,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: To::int($row->Id),
            name: To::str($row->Name),
            detail: To::str($row->Detail),
            navBar: To::str($row->NavBar),
            status: To::str($row->Status),
            onlyForMembers: To::int($row->OnlyForMembers),
            idGroup: $row->IdGroup !== null ? To::int($row->IdGroup) : null,
            nameOfDesigner: To::str($row->NameOfDesigner),
            votes: To::str($row->Votes),
        );
    }
}
