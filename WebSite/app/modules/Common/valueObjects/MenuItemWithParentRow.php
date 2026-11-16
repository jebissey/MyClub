<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

use stdClass;
use app\helpers\To;

final readonly class MenuItemWithParentRow
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $route,
        public ?int $idGroup,
        public int $forMembers,
        public int $forContacts,
        public int $forAnonymous,
        public string $what,
        public string $type,
        public string $label,
        public ?string $icon,
        public ?string $url,
        public ?string $groupName = null,
    ) {
    }

    public static function fromStdClass(stdClass $row): self
    {
        return new self(
            id: To::requireInt($row->Id),
            name: $row->Name !== null ? To::requireStr($row->Name) : null,
            route: $row->Route !== null ? To::requireStr($row->Route) : null,
            idGroup: $row->IdGroup !== null ? To::requireInt($row->IdGroup) : null,
            forMembers: To::requireInt($row->ForMembers),
            forContacts: To::requireInt($row->ForContacts),
            forAnonymous: To::requireInt($row->ForAnonymous),
            what: To::requireStr($row->What),
            type: To::requireStr($row->Type),
            label: To::requireStr($row->Label),
            icon: $row->Icon !== null ? To::requireStr($row->Icon) : null,
            url: $row->Url !== null ? To::requireStr($row->Url) : null,
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
