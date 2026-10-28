<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;

final readonly class MenuItemRow
{
    public function __construct(
        public int $id,
        public string $name,
        public string $route,
        public ?int $idGroup,
        public int $forMembers,
        public int $forContacts,
        public int $forAnonymous,
        public string $what,
        public string $type,
        public string $label,
        public ?string $icon,
        public string $url,
        public ?string $groupName = null,
    ) {
    }

    public static function fromStdClass(stdClass $o): self
    {
        return new self(
            id: (int) $o->Id,
            name: (string) $o->Name,
            route: (string) $o->Route,
            idGroup: $o->IdGroup !== null ? (int) $o->IdGroup : null,
            forMembers: (int) $o->ForMembers,
            forContacts: (int) $o->ForContacts,
            forAnonymous: (int) $o->ForAnonymous,
            what: (string) $o->What,
            type: (string) $o->Type,
            label: (string) $o->Label,
            icon: $o->Icon !== null ? (string) $o->Icon : null,
            url: (string) $o->Url,
        );
    }

    public function withGroupName(?string $groupName): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->route,
            $this->idGroup,
            $this->forMembers,
            $this->forContacts,
            $this->forAnonymous,
            $this->what,
            $this->type,
            $this->label,
            $this->icon,
            $this->url,
            $groupName,
        );
    }
}
