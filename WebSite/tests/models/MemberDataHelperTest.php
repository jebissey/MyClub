<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class MemberDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Individual columns read/filtered on in findForSignIn(), findByRememberToken(),
        // findBasicByEmail(), recordSignOutByEmail(), getActiveMemberEmails(),
        // getActiveMembersBasicInfo()
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
        // setRememberToken, setResetToken, finalizeReset, getActiveMemberEmails,
        // getActiveMembersBasicInfo)
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

    public function testGetActiveMemberEmailsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "
            SELECT Individual.Email
            FROM Individual
            INNER JOIN Member ON Member.Id = Individual.Id
            WHERE Member.Inactivated = 0
        ";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetActiveMembersBasicInfoSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "
            SELECT
                Individual.Id,
                Individual.NickName,
                Individual.FirstName,
                Individual.LastName,
                Individual.Email,
                Individual.Avatar,
                Member.UseGravatar
            FROM Member
            INNER JOIN Individual ON Individual.Id = Member.Id
            WHERE Member.Inactivated = 0
        ";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }
}
