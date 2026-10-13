<?php

declare(strict_types=1);

namespace tests\models;

class MemberDirectoryDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Alias "m" in every query using PersonDataHelper::MEMBER_INDIVIDUAL_JOIN
        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Inactivated',
            'UseGravatar',
            'Location',
            'MyPublicDataInPresentationDirectory',
            'InPresentationDirectory',
            'ShowPhoneInPresentationDirectory',
            'ShowEmailInPresentationDirectory',
        ]);

        // Alias "i" in the same queries
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
            'Phone',
            'FirstName',
            'LastName',
            'NickName',
            'Avatar',
        ]);

        // getPersonsInGroupForDirectory(): INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
        $this->assertColumnsExist($pdo, 'MemberGroup', [
            'IdMember',
            'IdGroup',
        ]);
    }
}
