<?php

declare(strict_types=1);

namespace tests\apis;

use tests\models\DataHelperTestCase;

class ChatApiTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'NickName',
            'FirstName',
            'LastName',
            'Email',
            'Avatar',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Inactivated',
            'UseGravatar',
        ]);
    }
}