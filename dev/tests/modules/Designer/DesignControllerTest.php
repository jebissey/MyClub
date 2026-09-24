<?php

declare(strict_types=1);

namespace tests\modules\Designer;

use tests\models\DataHelperTestCase;

class DesignControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Group : gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name')
        // dans index() et create()
        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'Inactivated',
        ]);

        // Design : set('Design', $values, ['Id' => ...]) dans save()
        $this->assertColumnsExist($pdo, 'Design', [
            'Id',
            'IdPerson',
            'Name',
            'Detail',
            'NavBar',
            'Status',
            'OnlyForMembers',
            'IdGroup',
        ]);
    }
}
