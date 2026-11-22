<?php

declare(strict_types=1);

namespace tests\models;

class MenuDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // getSidebarRows(): $this->gets('MenuItem', ['What' => 'sidebar'], 'Id, ParentId, Type, Label, Icon, Url, IdGroup, ForMembers, ForAnonymous', 'Position')
        $this->assertColumnsExist($pdo, 'MenuItem', [
            'Id',
            'ParentId',
            'Type',
            'Label',
            'Icon',
            'Url',
            'IdGroup',
            'ForMembers',
            'ForAnonymous',
            'What',
            'Position',
        ]);
    }
}
