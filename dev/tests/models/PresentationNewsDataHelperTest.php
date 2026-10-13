<?php

declare(strict_types=1);

namespace tests\models;

class PresentationNewsDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Alias "m" in getNews() (PersonDataHelper::MEMBER_INDIVIDUAL_JOIN)
        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'InPresentationDirectory',
            'PresentationLastUpdate',
        ]);

        // Alias "i" in getNews()
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
        ]);
    }
}
