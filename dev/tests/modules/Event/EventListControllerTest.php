<?php

declare(strict_types=1);

namespace tests\modules\Event;

use tests\models\DataHelperTestCase;

class EventListControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Languages: nextEventsHelp() reads the help text through renderHelp(),
        // selecting the column of the current language (fr_FR, en_US, pl_PL)
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);

        // NOTE: the lists of event types, need types and attributes are read by
        // EventLookupDataHelper (see EventLookupDataHelperTest), the events themselves
        // by EventListDataHelper.
    }
}