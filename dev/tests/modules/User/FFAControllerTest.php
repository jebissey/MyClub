<?php

declare(strict_types=1);

namespace tests\modules\User;

use tests\models\DataHelperTestCase;

class FFAControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // searchMember() récupère le club via Settings
        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);
    }
}
