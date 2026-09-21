<?php

declare(strict_types=1);

namespace tests\modules\Webmaster;

use tests\models\DataHelperTestCase;

class ArwardsControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // seeArwards()
        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'Inactivated',
        ]);

        // setArward()
        $this->assertColumnsExist($pdo, 'Counter', [
            'Name',
            'Detail',
            'Value',
            'IdPerson',
            'IdGroup',
        ]);
    }
}