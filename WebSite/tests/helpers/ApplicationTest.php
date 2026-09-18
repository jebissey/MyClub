<?php

declare(strict_types=1);

namespace tests\helpers;

use tests\models\DataHelperTestCase;

class ApplicationTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Metadata', [
            'Id',
            'Compact_everyXdays',
            'Compact_removeOlderThanXmonths',
            'Compact_compactOlderThanXmonths',
        ]);
    }
}