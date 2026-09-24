<?php

declare(strict_types=1);

namespace tests\modules\User;

use tests\models\DataHelperTestCase;

class UserPreferencesControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Preferences',
        ]);
    }
}
