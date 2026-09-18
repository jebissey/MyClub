<?php

declare(strict_types=1);

namespace tests\apis;

use tests\models\DataHelperTestCase;

class EventApiTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
        ]);
    }
}