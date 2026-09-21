<?php

declare(strict_types=1);

namespace tests\modules\Webmaster;

use tests\models\DataHelperTestCase;

class MaintenanceControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // checkIfSiteIsUnderMaintenance() / setSiteOnline() / setSiteUnderMaintenance()
        $this->assertColumnsExist($pdo, 'Metadata', [
            'Id',
            'SiteUnderMaintenance',
        ]);
    }
}