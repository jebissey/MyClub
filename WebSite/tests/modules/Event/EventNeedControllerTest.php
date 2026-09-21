<?php

declare(strict_types=1);

namespace tests\modules\Event;

use tests\models\DataHelperTestCase;

class EventNeedControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // NeedType : gets('NeedType', [], '*', 'Name') dans needs()
        $this->assertColumnsExist($pdo, 'NeedType', [
            'Id',
            'Name',
        ]);

        // NOTE : needDataHelper->getNeedsAndTheirTypes() délègue à NeedDataHelper,
        // déjà couvert par NeedDataHelperTest.
    }
}
