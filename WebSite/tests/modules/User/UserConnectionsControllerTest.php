<?php

declare(strict_types=1);

namespace tests\modules\User;

use tests\models\DataHelperTestCase;

class UserConnectionsControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
            'NickName',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'InPresentationDirectory',
            'ShowPhoneInPresentationDirectory',
            'ShowEmailInPresentationDirectory',
        ]);
    }
}
