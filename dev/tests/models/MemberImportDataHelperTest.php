<?php

declare(strict_types=1);

namespace tests\models;

class MemberImportDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Type',
            'Email',
            'FirstName',
            'LastName',
            'Phone',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Imported',
            'Inactivated',
            'CustomFields',
        ]);
    }
}
