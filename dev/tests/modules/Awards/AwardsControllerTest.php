<?php

declare(strict_types=1);

namespace tests\modules\Awards;

use tests\models\DataHelperTestCase;

class AwardsControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // seeAwards()
        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'Inactivated',
        ]);

        // setAward()
        $this->assertColumnsExist($pdo, 'Counter', [
            'Name',
            'Detail',
            'Value',
            'IdPerson',
            'IdGroup',
        ]);
    }
}