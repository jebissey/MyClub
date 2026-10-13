<?php

declare(strict_types=1);

namespace tests\apis;

use tests\models\DataHelperTestCase;

class MessageApiTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Message', [
            'Id',
            'PersonId',
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'CreatedBy',
            'Title',
        ]);

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'CreatedBy',
            'Summary',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
        ]);
    }
}
