<?php

declare(strict_types=1);

namespace tests\helpers;

use tests\models\DataHelperTestCase;

class PersonPreferencesTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Preferences',
            'Availabilities',
        ]);
    }
}