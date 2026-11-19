<?php

declare(strict_types=1);

namespace tests\helpers;

use tests\models\DataHelperTestCase;

class ConnectedUserTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // get(): $this->get('Individual', ..., 'Id, Email, FirstName, LastName, NickName, Avatar')
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
            'NickName',
            'Avatar',
        ]);

        // get(): $this->get('Member', ..., 'Alert, UseGravatar')
        // getLastSignIn(): $this->get('Member', ..., 'LastSignIn')
        // getLastSignOut(): $this->get('Member', ..., 'LastSignOut')
        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Alert',
            'UseGravatar',
            'LastSignIn',
            'LastSignOut',
        ]);
    }
}
