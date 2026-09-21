<?php

declare(strict_types=1);

namespace tests\apis;

use tests\models\DataHelperTestCase;

class EventNeedApiTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Need : delete('Need', ['Id' => $id]) dans deleteNeed(),
        // set('Need', $needData, ...) dans doSaveNeed()
        $this->assertColumnsExist($pdo, 'Need', [
            'Id',
            'Label',
            'Name',
            'ParticipantDependent',
            'IdNeedType',
        ]);

        // NOTE : eventDataHelper->eventExists() et eventNeedDataHelper->needsForEvent()
        // délèguent à des data helpers dédiés, à couvrir séparément dans leurs
        // propres tests.
    }
}
