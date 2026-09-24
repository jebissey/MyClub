<?php

declare(strict_types=1);

namespace tests\models;

class ReplyDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Reply', [
            'Id',
            'IdPerson',
            'IdSurvey',
            'Answers',
            'LastUpdate',
        ]);
    }
}