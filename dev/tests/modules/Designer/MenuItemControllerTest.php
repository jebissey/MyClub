<?php

declare(strict_types=1);

namespace tests\modules\Designer;

use tests\models\DataHelperTestCase;

class MenuItemControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // index() / showArwards()
        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'Inactivated',
        ]);

        // showArticle()
        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'Content',
        ]);
    }
}