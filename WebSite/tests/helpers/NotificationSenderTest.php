<?php

declare(strict_types=1);

namespace tests\helpers;

use tests\models\DataHelperTestCase;

class NotificationSenderTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'PushSubscription', [
            'IdPerson',
            'EndPoint',
            'Auth',
            'P256dh',
        ]);
    }
}