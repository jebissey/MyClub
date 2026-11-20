<?php

declare(strict_types=1);

namespace tests\models;

class EventLookupDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // getActiveEventTypes()
        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Name',
            'Inactivated',
            'IdGroup',
        ]);

        // getNeedTypes()
        $this->assertColumnsExist($pdo, 'NeedType', [
            'Id',
            'Name',
        ]);

        // getAllAttributes()
        $this->assertColumnsExist($pdo, 'Attribute', [
            'Id',
            'Name',
            'Detail',
            'Color',
        ]);
    }
}
