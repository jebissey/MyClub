<?php

declare(strict_types=1);

namespace tests\modules\Event;

use tests\models\DataHelperTestCase;

class EventRegistrationControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Event: eventExists(), findAudience() and findRegistrationInfo() in EventDataHelper
        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'Audience',
            'Summary',
            'StartTime',
            'Location',
        ]);

        // NOTE: the whole registration logic (Individual, Contact, Invitation, Participant)
        // is delegated to ExternalContactRegistrationService and the invitation and
        // participation DataHelpers (see EventDataHelperTest).
    }
}