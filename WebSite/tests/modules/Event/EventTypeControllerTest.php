<?php

declare(strict_types=1);

namespace tests\modules\Event;

use tests\models\DataHelperTestCase;

class EventTypeControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // EventType : set() dans create()/delete(), get() dans edit()/eventTypeExists()
        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Name',
            'IdGroup',
            'Inactivated',
        ]);

        // EventTypeAttribute : gets('EventTypeAttribute', ..., 'IdAttribute') dans edit()
        $this->assertColumnsExist($pdo, 'EventTypeAttribute', [
            'IdEventType',
            'IdAttribute',
        ]);

        // Group : gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name') dans edit()
        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'Inactivated',
        ]);

        // Attribute : gets('Attribute', [], '*', 'Name') dans edit()
        $this->assertColumnsExist($pdo, 'Attribute', [
            'Id',
            'Name',
            'Detail',
            'Color',
        ]);

        // NOTE : eventTypeDataHelper->update() et tableControllerDataHelper->
        // getEventTypesQuery() délèguent à des data helpers dédiés, à couvrir
        // séparément dans leurs propres tests.
    }
}
