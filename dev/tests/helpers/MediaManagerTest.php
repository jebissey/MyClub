<?php

declare(strict_types=1);

namespace tests\helpers;

use tests\models\DataHelperTestCase;

class MediaManagerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'SharedFile', [
            'Id',
            'Item',
            'IdGroup',
            'OnlyForMembers',
            'Token',
        ]);
    }
}