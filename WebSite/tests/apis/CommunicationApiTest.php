<?php

declare(strict_types=1);

namespace tests\apis;

use tests\models\DataHelperTestCase;

class CommunicationApiTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Settings : set('Settings', ['Name' => 'contactEmail', 'Value' => ...])
        // dans updateContactEmail()
        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);

        // NOTE : personDataHelper->getPersonsForCommunication()/getAllPersons()
        // et languagesDataHelper->translate() délèguent à des data helpers
        // dédiés, à couvrir séparément dans leurs propres tests.
    }
}
