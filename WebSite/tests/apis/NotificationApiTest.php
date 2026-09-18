<?php

declare(strict_types=1);

namespace tests\apis;

use tests\models\DataHelperTestCase;

class NotificationApiTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'PushSubscription', [
            'Id',
            'IdPerson',
            'EndPoint',
            'Auth',
            'P256dh',
        ]);
    }
}