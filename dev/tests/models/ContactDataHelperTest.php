<?php

declare(strict_types=1);

namespace tests\models;

class ContactDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // issueRegistrationToken(): $this->get('Individual', ['Email' => ...], '*')
        // then $this->set('Individual', [...]) to create the contact's identity
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Type',
            'Email',
            'FirstName',
            'LastName',
            'NickName',
        ]);

        // issueRegistrationToken(): $this->set('Contact', [...]) on creation,
        // and $this->set('Contact', [...], ['Id' => ...]) on token refresh
        $this->assertColumnsExist($pdo, 'Contact', [
            'Id',
            'Token',
            'TokenCreatedAt',
        ]);
    }
}
