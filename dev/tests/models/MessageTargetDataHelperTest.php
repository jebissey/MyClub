<?php // dev/tests/models/MessageTargetDataHelperTest.php

declare(strict_types=1);

namespace tests\models;

class MessageTargetDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // findArticle(): $this->get('Article', ['Id' => ...], 'CreatedBy, Title')
        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'CreatedBy',
            'Title',
        ]);

        // findEvent(): $this->get('Event', ['Id' => ...], 'CreatedBy, Summary')
        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'CreatedBy',
            'Summary',
        ]);

        // findGroup(): $this->get('Group', ['Id' => ...], 'Name')
        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
        ]);
    }
}
