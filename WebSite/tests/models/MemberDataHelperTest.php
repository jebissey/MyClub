<?php

declare(strict_types=1);

namespace tests\models;

class MemberDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Individual columns read/filtered on in findForSignIn(), findByRememberToken(),
        // findBasicByEmail(), recordSignOutByEmail()
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
            'NickName',
            'Avatar',
        ]);

        // Member columns read/written across all methods (findForSignIn, findByRememberToken,
        // findBasicByEmail, findByResetToken, recordSignIn, recordSignOutByEmail,
        // setRememberToken, setResetToken, finalizeReset)
        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Password',
            'Inactivated',
            'UseGravatar',
            'Alert',
            'Token',
            'TokenCreatedAt',
            'LastSignIn',
            'LastSignOut',
        ]);
    }
}