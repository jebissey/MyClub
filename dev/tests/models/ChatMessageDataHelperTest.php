<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class ChatMessageDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Message columns read/filtered on in getArticleMessages(), getEventMessages(),
        // getGroupMessages() (Message.* is selected by the first two)
        $this->assertColumnsExist($pdo, 'Message', [
            'Id',
            'PersonId',
            'Text',
            'ImagePath',
            'LastUpdate',
            'ArticleId',
            'EventId',
            'GroupId',
            'From',
        ]);

        // Individual columns read in all three methods
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'FirstName',
            'LastName',
            'NickName',
            'Avatar',
            'Email',
        ]);

        // Member columns read/joined on in all three methods
        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'UseGravatar',
        ]);
    }

    public function testGetArticleMessagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "
            SELECT
                Message.*,
                Individual.FirstName,
                Individual.LastName,
                Individual.NickName,
                Individual.Avatar,
                Member.UseGravatar,
                Individual.Email
            FROM Message
            LEFT JOIN Member     ON Message.PersonId = Member.Id
            LEFT JOIN Individual ON Member.Id = Individual.Id
            WHERE Message.ArticleId = :articleId
            ORDER BY Message.Id ASC
        ";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetEventMessagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "
            SELECT
                Message.*,
                Individual.FirstName,
                Individual.LastName,
                Individual.NickName,
                Individual.Avatar,
                Member.UseGravatar,
                Individual.Email
            FROM Message
            LEFT JOIN Member     ON Message.PersonId = Member.Id
            LEFT JOIN Individual ON Member.Id = Individual.Id
            WHERE Message.EventId = :eventId
              AND Message.\"From\" = 'User'
            ORDER BY Message.Id ASC
        ";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testGetGroupMessagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "
            SELECT
                Message.Id,
                Message.PersonId,
                Message.Text,
                Message.ImagePath,
                Message.LastUpdate,
                Individual.FirstName,
                Individual.LastName,
                Individual.NickName,
                Individual.Avatar,
                Member.UseGravatar,
                Individual.Email
            FROM Message
            LEFT JOIN Member     ON Message.PersonId = Member.Id
            LEFT JOIN Individual ON Member.Id = Individual.Id
            WHERE Message.GroupId = :groupId
            ORDER BY Message.Id ASC
        ";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }
}