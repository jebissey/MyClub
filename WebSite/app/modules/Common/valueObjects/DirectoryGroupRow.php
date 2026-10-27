<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * @phpstan-type DirectoryGroupRowShape object{
 *     Id: int|string,
 *     Name: string
 * }
 */
final readonly class DirectoryGroupRow
{
    public function __construct(
        public int $id,
        public string $name,
        public string $url = '',
    ) {
    }

    /**
     * Returns a copy of this row with the directory URL set.
     */
    public function withUrl(string $url): self
    {
        return new self($this->id, $this->name, $url);
    }

    /**
     * @param DirectoryGroupRowShape $row
     */
    public static function fromStdClass(object $row): self
    {
        return new self(
            id: (int) $row->Id,
            name: (string) $row->Name,
        );
    }
}
