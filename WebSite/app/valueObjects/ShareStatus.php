<?php

declare(strict_types=1);

namespace app\valueObjects;

final readonly class ShareStatus
{
    public function __construct(
        public bool $shared,
        public ?int $idGroup = null,
        public ?bool $membersOnly = null,
        public ?string $link = null,
    ) {
    }

    /**
     * @return array{shared: false}|array{shared: true, idGroup: int|null, membersOnly: bool, link: string}
     */
    public function toArray(): array
    {
        if (!$this->shared) {
            return ['shared' => false];
        }

        return [
            'shared' => true,
            'idGroup' => $this->idGroup,
            'membersOnly' => $this->membersOnly ?? false,
            'link' => $this->link ?? '',
        ];
    }
}
