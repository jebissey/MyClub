<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class MessageWriteDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Message columns written/read across addMessage(), addWebAppMessages(), updateMessage()
        $this->assertColumnsExist($pdo, 'Message', [
            'Id',
            'ArticleId',
            'EventId',
            'GroupId',
            'PersonId',
            'Text',
            'From',
            'ImagePath',
            'LastUpdate',
        ]);

        // Target tables checked for existence in addMessage() through MessageTarget::reference()
        $this->assertColumnsExist($pdo, 'Article', ['Id']);
        $this->assertColumnsExist($pdo, 'Event', ['Id']);
    }

    public function testAddMessageInsertSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "
            INSERT INTO Message (ArticleId, EventId, GroupId, PersonId, Text, \"From\", ImagePath)
            VALUES (:articleId, :eventId, :groupId, :personId, :text, :from, :imagePath)
        ";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testUpdateMessageSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "UPDATE Message SET Text = :text, LastUpdate = :lastUpdate WHERE Id = :id";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }
}