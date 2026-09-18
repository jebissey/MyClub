<?php

declare(strict_types=1);

namespace tests\apis;

use tests\models\DataHelperTestCase;

class MenuItemApiTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'MenuItem', [
            'Id',
            'What',
            'Type',
            'Label',
            'Icon',
            'Url',
            'ParentId',
            'Position',
            'IdGroup',
            'ForMembers',
            'ForContacts',
            'ForAnonymous',
        ]);
    }
}