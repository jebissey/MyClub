<?php

declare(strict_types=1);

namespace app\modules\User\services;

use app\helpers\Application;
use app\models\AuthorizationDataHelper;
use app\models\MenuDataHelper;
use app\modules\Common\valueObjects\Person;
use app\modules\Common\valueObjects\SidebarMenuChild;
use app\modules\Common\valueObjects\SidebarMenuItem;
use app\modules\Common\valueObjects\SidebarMenuRow;

final class SidebarMenuBuilder
{
    public function __construct(
        private readonly MenuDataHelper $menuDataHelper,
        private readonly AuthorizationDataHelper $authorizationDataHelper
    ) {
    }

    /**
     * @return list<SidebarMenuItem>
     */
    public function build(?Person $person, bool $all = false): array
    {
        $visibleRows = $this->filterVisibleRows($person, $all);

        $menu = [];
        foreach ($visibleRows as $row) {
            // Children are attached to their parent submenu
            if ($row->parentId !== null) {
                continue;
            }
            $menu[] = $this->toMenuItem($row, $visibleRows);
        }

        return $menu;
    }

    /**
     * @return array<int, SidebarMenuRow>
     */
    private function filterVisibleRows(?Person $person, bool $all): array
    {
        $userGroups = $person !== null
            ? $this->authorizationDataHelper->getUserGroups($person->email)
            : [];

        $visible = [];
        foreach ($this->menuDataHelper->getSidebarRows() as $row) {
            if ($all || $this->isVisibleFor($row, $person, $userGroups)) {
                $visible[$row->id] = $row;
            }
        }

        return $visible;
    }

    /**
     * @param array<array-key, mixed> $userGroups
     */
    private function isVisibleFor(SidebarMenuRow $row, ?Person $person, array $userGroups): bool
    {
        if ($person === null) {
            return $row->forAnonymous;
        }

        return $row->forMembers
            && ($row->groupId === null || in_array($row->groupId, $userGroups, true));
    }

    /**
     * @param array<int, SidebarMenuRow> $visibleRows
     */
    private function toMenuItem(SidebarMenuRow $row, array $visibleRows): SidebarMenuItem
    {
        return match ($row->type) {
            'heading' => new SidebarMenuItem(type: 'heading', label: $row->label),
            'divider' => new SidebarMenuItem(type: 'divider'),
            'link'    => new SidebarMenuItem(
                type: 'link',
                label: $row->label,
                icon: $row->icon,
                url: $row->url,
            ),
            'submenu' => new SidebarMenuItem(
                type: 'submenu',
                label: $row->label,
                icon: $row->icon,
                children: $this->buildChildren($row->id, $visibleRows),
            ),
            default => Application::unreachable('Unknown sidebar item type=' . $row->type),
        };
    }

    /**
     * @param array<int, SidebarMenuRow> $visibleRows
     * @return list<SidebarMenuChild>
     */
    private function buildChildren(int $parentId, array $visibleRows): array
    {
        $children = array_filter(
            $visibleRows,
            static fn(SidebarMenuRow $child) => $child->parentId === $parentId
        );

        return array_values(array_map(
            static fn(SidebarMenuRow $child) => new SidebarMenuChild(
                label: $child->label,
                url: $child->url,
            ),
            $children
        ));
    }
}
