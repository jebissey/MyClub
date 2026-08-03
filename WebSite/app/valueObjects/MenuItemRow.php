<?php

declare(strict_types=1);

namespace app\valueObjects;

use stdClass;

final readonly class MenuItemRow
{
    public function __construct(
        public int $Id,
        public string $Name,
        public string $Route,
        public ?int $IdGroup,
        public int $ForMembers,
        public int $ForContacts,
        public int $ForAnonymous,
        public string $What,
        public string $Type,
        public string $Label,
        public ?string $Icon,
        public string $Url,
        public ?string $GroupName = null,
    ) {
    }

    public static function fromStdClass(stdClass $o): self
    {
        return new self(
            Id: (int) $o->Id,
            Name: (string) $o->Name,
            Route: (string) $o->Route,
            IdGroup: $o->IdGroup !== null ? (int) $o->IdGroup : null,
            ForMembers: (int) $o->ForMembers,
            ForContacts: (int) $o->ForContacts,
            ForAnonymous: (int) $o->ForAnonymous,
            What: (string) $o->What,
            Type: (string) $o->Type,
            Label: (string) $o->Label,
            Icon: $o->Icon !== null ? (string) $o->Icon : null,
            Url: (string) $o->Url,
        );
    }

    public function withGroupName(?string $groupName): self
    {
        return new self(
            $this->Id,
            $this->Name,
            $this->Route,
            $this->IdGroup,
            $this->ForMembers,
            $this->ForContacts,
            $this->ForAnonymous,
            $this->What,
            $this->Type,
            $this->Label,
            $this->Icon,
            $this->Url,
            $groupName,
        );
    }
}
