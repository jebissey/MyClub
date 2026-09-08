<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

final readonly class GroupNameRow
{
    public function __construct(
        public string $Name,
    ) {
    }

    /**
     * @param object{Name:string} $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            Name: $row->Name,
        );
    }
}
