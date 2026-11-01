<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;
use app\helpers\To;

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
            id: To::int($o->Id),
            name: To::str($o->Name),
            route: To::str($o->Route),
            idGroup: $o->IdGroup !== null ? To::int($o->IdGroup) : null,
            forMembers: To::int($o->ForMembers),
            forContacts: To::int($o->ForContacts),
            forAnonymous: To::int($o->ForAnonymous),
            what: To::str($o->What),
            type: To::str($o->Type),
            label: To::str($o->Label),
            icon: $o->Icon !== null ? To::str($o->Icon) : null,
            url: To::str($o->Url),
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
