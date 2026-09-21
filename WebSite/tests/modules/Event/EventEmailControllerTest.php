<?php

declare(strict_types=1);

namespace tests\modules\Event;

use tests\models\DataHelperTestCase;

class EventEmailControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Group : gets()/get() dans fetchEmails()/copyEmails()
        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'Inactivated',
        ]);

        // EventType : gets()/get() dans fetchEmails()/copyEmails()
        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Name',
            'Inactivated',
        ]);

        // NOTE : personDataHelper->getEmailsOfInterestedPeople() et
        // ->getActiveMembersContactInfoByEmail() (Individual/Member) délèguent
        // à PersonDataHelper, à couvrir dans PersonDataHelperTest.
    }
}
