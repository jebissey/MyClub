<?php

declare(strict_types=1);

namespace tests\models;

class EventInvitationDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Invitation', [
            'Id',
            'Email',
            'NickName',
            'IdEvent',
            'Token',
            'InvitedBy',
            'InvitedAt',
        ]);

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Type',
            'Email',
            'FirstName',
            'LastName',
            'NickName',
        ]);

        $this->assertColumnsExist($pdo, 'Contact', [
            'Id',
            'Token',
            'TokenCreatedAt',
        ]);
    }
}