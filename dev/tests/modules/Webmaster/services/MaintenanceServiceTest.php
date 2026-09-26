<?php

declare(strict_types=1);

namespace tests\modules\Webmaster\services;

use tests\models\DataHelperTestCase;

final class MaintenanceServiceTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Metadata', [
            'Id',
            'SiteUnderMaintenance',
        ]);
    }
}
