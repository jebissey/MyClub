<?php

declare(strict_types=1);

namespace tests\modules\Backup;

use tests\models\DataHelperTestCase;

class BackupControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Read by getLastBackupDate() and written by download()
        $this->assertColumnsExist($pdo, 'Metadata', [
            'Id',
            'Backup_lastDate',
        ]);
    }
}