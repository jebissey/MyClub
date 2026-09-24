<?php

declare(strict_types=1);

namespace tests\modules\User;

use tests\models\DataHelperTestCase;

class ContactControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'Summary',
            'StartTime',
            'Audience',
        ]);

        $this->assertColumnsExist($pdo, 'ContactRateLimit', [
            'ip_hash',
            'attempts',
            'since',
        ]);

        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);
    }
}
