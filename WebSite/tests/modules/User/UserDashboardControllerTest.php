<?php

declare(strict_types=1);

namespace tests\modules\User;

use tests\models\DataHelperTestCase;

class UserDashboardControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'en_US',
            'fr_FR',
            'pl_PL',
        ]);
    }
}
