<?php

declare(strict_types=1);

namespace app\models;

use stdClass;
use app\helpers\Application;
use app\helpers\To;
use app\modules\Common\valueObjects\SidebarMenuRow;

final class MenuDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * Returns every sidebar menu item, ordered by position.
     *
     * @return list<SidebarMenuRow>
     */
    public function getSidebarRows(): array
    {
        $records = $this->gets(
            'MenuItem',
            ['What' => 'sidebar'],
            'Id, ParentId, Type, Label, Icon, Url, IdGroup, ForMembers, ForAnonymous',
            'Position'
        );

        return array_values(array_map(
            static fn(stdClass $row) => new SidebarMenuRow(
                id: To::int($row->Id),
                parentId: $row->ParentId !== null ? To::int($row->ParentId) : null,
                type: To::str($row->Type),
                label: To::str($row->Label),
                icon: $row->Icon !== null ? To::str($row->Icon) : null,
                url: $row->Url !== null ? To::str($row->Url) : null,
                groupId: $row->IdGroup !== null ? To::int($row->IdGroup) : null,
                forMembers: To::int($row->ForMembers) === 1,
                forAnonymous: To::int($row->ForAnonymous) === 1
            ),
            $records
        ));
    }
}
