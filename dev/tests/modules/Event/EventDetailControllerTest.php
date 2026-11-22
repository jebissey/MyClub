<?php

declare(strict_types=1);

namespace tests\modules\Event;

use tests\models\DataHelperTestCase;

class EventDetailControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Event: eventExists(), getEvent() and findChatRow() in EventDataHelper
        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'Audience',
            'CreatedBy',
            'Summary',
            'StartTime',
            'Duration',
            'Location',
        ]);

        // Message: MessageStatisticsDataHelper::countUserMessagesForEvent() in show()
        $this->assertColumnsExist($pdo, 'Message', [
            'From',
            'EventId',
        ]);

        // NOTE: participants, needs, supplies, attributes and carpooling data are read by
        // their own DataHelpers (see EventDataHelperTest and the corresponding tests).
    }
}