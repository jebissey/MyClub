<?php

declare(strict_types=1);

namespace tests\apis;

use PDO;
use tests\models\DataHelperTestCase;

class GroupApiTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
        ]);

        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'Id',
            'IdMember',
            'IdGroup',
        ]);
    }

    public function testLegacyPersonGroupTableIsGone(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='PersonGroup'")
            ->fetchAll(PDO::FETCH_COLUMN);

        $this->assertSame([], $tables, 'PersonGroup should have been replaced by MemberGroup in V81');
    }
}