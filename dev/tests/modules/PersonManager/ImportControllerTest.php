<?php

declare(strict_types=1);

namespace tests\modules\PersonManager;

use tests\models\DataHelperTestCase;

class ImportControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Settings : get()/set() dans loadSettings() et processImport()
        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);

        // NOTE : personDataHelper->importFromCsvFile() et ->getAllPersons()
        // délèguent à PersonDataHelper — probablement le point le plus sensible
        // vis-à-vis de la migration Person -> Individual/Member (création de
        // nouvelles personnes à l'import). À couvrir dans PersonDataHelperTest.
    }
}