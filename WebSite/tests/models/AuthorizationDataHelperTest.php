<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class AuthorizationDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Person', [
            'Id',
            'Email',
        ]);

        $this->assertColumnsExist($pdo, 'PersonGroup', [
            'IdPerson',
            'IdGroup',
        ]);

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
        ]);

        $this->assertColumnsExist($pdo, 'GroupAuthorization', [
            'IdGroup',
            'IdAuthorization',
        ]);

        $this->assertColumnsExist($pdo, 'Authorization', [
            'Id',
            'Name',
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'CreatedBy',
            'PublishedBy',
            'OnlyForMembers',
            'IdGroup',
            'Content',
        ]);

        $this->assertColumnsExist($pdo, 'Carousel', [
            'IdArticle',
            'Item',
        ]);

        $this->assertColumnsExist($pdo, 'Message', [
            'ArticleId',
            'EventId',
            'GroupId',
            'ImagePath',
        ]);

        $this->assertColumnsExist($pdo, 'OrderReply', [
            'IdOrder',
            'IdPerson',
        ]);

        $this->assertColumnsExist($pdo, 'Reply', [
            'IdSurvey',
            'IdPerson',
        ]);

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'IdEventType',
            'Audience',
        ]);

        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Inactivated',
            'IdGroup',
        ]);
    }

    public function testGetsForSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getsForSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT DISTINCT Authorization.Name FROM Person', $sql);
        $this->assertStringContainsString('INNER JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson', $sql);
        $this->assertStringContainsString('INNER JOIN `Group` ON PersonGroup.IdGroup = `Group`.Id', $sql);
        $this->assertStringContainsString('INNER JOIN GroupAuthorization on `Group`.Id = GroupAuthorization.IdGroup', $sql);
        $this->assertStringContainsString('INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id', $sql);
        $this->assertStringContainsString('WHERE Person.Id = ?', $sql);
    }

    public function testPersonCanReadMediaFileArticlesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getPersonCanReadMediaFileArticlesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT a.CreatedBy, a.PublishedBy, a.OnlyForMembers, a.IdGroup, a.Id', $sql);
        $this->assertStringContainsString('FROM Article a', $sql);
        $this->assertStringContainsString('WHERE a.Content LIKE :pattern', $sql);
        $this->assertStringContainsString('UNION', $sql);
        $this->assertStringContainsString('INNER JOIN Carousel c ON c.IdArticle = a.Id', $sql);
        $this->assertStringContainsString('WHERE c.Item LIKE :pattern', $sql);
    }

    public function testPersonCanReadMediaFileMessagesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getPersonCanReadMediaFileMessagesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT ArticleId, EventId, GroupId', $sql);
        $this->assertStringContainsString('FROM Message', $sql);
        $this->assertStringContainsString('WHERE ImagePath LIKE :pattern', $sql);
    }

    public function testCanPersonReadOrderResultsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCanPersonReadOrderResultsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*) FROM OrderReply WHERE IdOrder = ? AND IdPerson = ?', $sql);
    }

    public function testCanPersonReadSurveyResultsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCanPersonReadSurveyResultsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*) FROM Reply WHERE IdSurvey = ? AND IdPerson = ?', $sql);
    }

    public function testGetUserGroupsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUserGroupsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT PersonGroup.IdGroup AS IdGroup', $sql);
        $this->assertStringContainsString('FROM PersonGroup', $sql);
        $this->assertStringContainsString('LEFT JOIN Person ON Person.Id = PersonGroup.IdPerson', $sql);
        $this->assertStringContainsString('WHERE Person.Email COLLATE NOCASE = :email', $sql);
    }

    public function testCanReadEventByIdAnonymousSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCanReadEventByIdAnonymousSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT e.Id FROM Event e', $sql);
        $this->assertStringContainsString('JOIN EventType et ON et.Id = e.IdEventType', $sql);
        $this->assertStringContainsString('WHERE e.Id = :id', $sql);
        $this->assertStringContainsString('AND et.Inactivated = 0', $sql);
        $this->assertStringContainsString('AND et.IdGroup IS NULL', $sql);
        $this->assertStringContainsString('AND e.Audience = :audience', $sql);
    }

    public function testCanReadEventByIdMemberSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCanReadEventByIdMemberSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT e.Id FROM Event e', $sql);
        $this->assertStringContainsString('JOIN EventType et ON et.Id = e.IdEventType', $sql);
        $this->assertStringContainsString('WHERE e.Id = :id', $sql);
        $this->assertStringContainsString('AND et.Inactivated = 0', $sql);
        $this->assertStringContainsString('et.IdGroup IS NULL', $sql);
        $this->assertStringContainsString('OR et.IdGroup IN (', $sql);
        $this->assertStringContainsString('SELECT IdGroup FROM PersonGroup WHERE IdPerson = :personId', $sql);
    }

    private function getsForSql(): string
    {
        return "
            SELECT DISTINCT Authorization.Name FROM Person 
            INNER JOIN PersonGroup ON Person.Id = PersonGroup.IdPerson
            INNER JOIN `Group` ON PersonGroup.IdGroup = `Group`.Id
            INNER JOIN GroupAuthorization on `Group`.Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id 
            WHERE Person.Id = ?";
    }

    private function getPersonCanReadMediaFileArticlesSql(): string
    {
        return "
            SELECT a.CreatedBy, a.PublishedBy, a.OnlyForMembers, a.IdGroup, a.Id
            FROM Article a
            WHERE a.Content LIKE :pattern

            UNION

            SELECT a.CreatedBy, a.PublishedBy, a.OnlyForMembers, a.IdGroup, a.Id
            FROM Article a
            INNER JOIN Carousel c ON c.IdArticle = a.Id
            WHERE c.Item LIKE :pattern";
    }

    private function getPersonCanReadMediaFileMessagesSql(): string
    {
        return "
            SELECT ArticleId, EventId, GroupId
            FROM Message
            WHERE ImagePath LIKE :pattern";
    }

    private function getCanPersonReadOrderResultsSql(): string
    {
        return 'SELECT COUNT(*) FROM OrderReply WHERE IdOrder = ? AND IdPerson = ?';
    }

    private function getCanPersonReadSurveyResultsSql(): string
    {
        return 'SELECT COUNT(*) FROM Reply WHERE IdSurvey = ? AND IdPerson = ?';
    }

    private function getUserGroupsSql(): string
    {
        return '
            SELECT PersonGroup.IdGroup AS IdGroup
            FROM PersonGroup
            LEFT JOIN Person ON Person.Id = PersonGroup.IdPerson
            WHERE Person.Email COLLATE NOCASE = :email
        ';
    }

    private function getCanReadEventByIdAnonymousSql(): string
    {
        return "
                SELECT e.Id FROM Event e
                JOIN EventType et ON et.Id = e.IdEventType
                WHERE e.Id = :id
                  AND et.Inactivated = 0
                  AND et.IdGroup IS NULL
                  AND e.Audience = :audience
            ";
    }

    private function getCanReadEventByIdMemberSql(): string
    {
        return "
                SELECT e.Id FROM Event e
                JOIN EventType et ON et.Id = e.IdEventType
                WHERE e.Id = :id
                  AND et.Inactivated = 0
                  AND (
                      et.IdGroup IS NULL
                      OR et.IdGroup IN (
                          SELECT IdGroup FROM PersonGroup WHERE IdPerson = :personId
                      )
                  )
            ";
    }
}