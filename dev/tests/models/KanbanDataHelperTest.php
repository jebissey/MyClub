<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class KanbanDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'KanbanProject', [
            'Id',
            'IdPerson',
            'Title',
            'Detail',
        ]);

        $this->assertColumnsExist($pdo, 'KanbanCardType', [
            'Id',
            'IdKanbanProject',
            'Label',
            'Detail',
            'Color',
        ]);

        $this->assertColumnsExist($pdo, 'KanbanCard', [
            'Id',
            'IdKanbanCardType',
            'Title',
            'Detail',
        ]);

        $this->assertColumnsExist($pdo, 'KanbanCardStatus', [
            'Id',
            'IdKanbanCard',
            'What',
            'Remark',
            'LastUpdate',
        ]);
    }

    public function testCreateKanbanCardSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCreateKanbanCardSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO KanbanCard', $sql);
        $this->assertStringContainsString('IdKanbanCardType', $sql);
        $this->assertStringContainsString('Title', $sql);
        $this->assertStringContainsString('Detail', $sql);
    }

    public function testDeleteKanbanCardSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $deleteStatusSql = $this->getDeleteKanbanCardStatusSql();
        $deleteCardSql = $this->getDeleteKanbanCardSql();

        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($deleteStatusSql));
        $this->assertInstanceOf(PDOStatement::class, $pdo->prepare($deleteCardSql));

        $this->assertStringContainsString('DELETE FROM KanbanCardStatus', $deleteStatusSql);
        $this->assertStringContainsString('IdKanbanCard', $deleteStatusSql);
        $this->assertStringContainsString('DELETE FROM KanbanCard', $deleteCardSql);
        $this->assertStringContainsString('JOIN KanbanCardType', $deleteCardSql);
        $this->assertStringContainsString('JOIN KanbanProject', $deleteCardSql);
        $this->assertStringContainsString('kp.IdPerson = :idPerson', $deleteCardSql);
    }

    public function testGetKanbanProjectSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetKanbanProjectSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM KanbanProject', $sql);
        $this->assertStringContainsString('Id, Title, Detail', preg_replace('/\s+/', ' ', $sql) ?? $sql);
        $this->assertStringContainsString('WHERE Id = :id', $sql);
    }

    public function testGetKanbanProjectsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetKanbanProjectsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM KanbanProject', $sql);
        $this->assertStringContainsString('ORDER BY Title', $sql);
    }

    public function testMoveKanbanCardSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getMoveKanbanCardSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO KanbanCardStatus', $sql);
        $this->assertStringContainsString('IdKanbanCard', $sql);
        $this->assertStringContainsString('What', $sql);
        $this->assertStringContainsString('Remark', $sql);
        $this->assertStringContainsString('LastUpdate', $sql);
    }

    public function testUpdateKanbanCardSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUpdateKanbanCardSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE KanbanCard', $sql);
        $this->assertStringContainsString('SET Title = :title, Detail = :detail', $sql);
        $this->assertStringContainsString('JOIN KanbanCardType', $sql);
        $this->assertStringContainsString('JOIN KanbanProject', $sql);
        $this->assertStringContainsString('kp.IdPerson = :idPerson', $sql);
    }

    public function testUpdateKanbanCardStatusSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUpdateKanbanCardStatusSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE KanbanCardStatus', $sql);
        $this->assertStringContainsString('SET Remark = :remark, LastUpdate = :lastUpdate', $sql);
        $this->assertStringContainsString('JOIN KanbanCard', $sql);
        $this->assertStringContainsString('JOIN KanbanCardType', $sql);
        $this->assertStringContainsString('JOIN KanbanProject', $sql);
        $this->assertStringContainsString('kp.IdPerson = :idPerson', $sql);
    }

    public function testGetKanbanHistorySqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetKanbanHistorySql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM KanbanCardStatus', $sql);
        $this->assertStringContainsString('WHEN What = \'Created\'', $sql);
        $this->assertStringContainsString('ToBacklog', $sql);
        $this->assertStringContainsString('ToSelected', $sql);
        $this->assertStringContainsString('ToInProgress', $sql);
        $this->assertStringContainsString('ToDone', $sql);
        $this->assertStringContainsString('WHERE IdKanbanCard = :idKanbanCard', $sql);
        $this->assertStringContainsString('ORDER BY Id', $sql);
    }

    public function testCreateKanbanProjectSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCreateKanbanProjectSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO KanbanProject', $sql);
        $this->assertStringContainsString('IdPerson', $sql);
        $this->assertStringContainsString('Title', $sql);
        $this->assertStringContainsString('Detail', $sql);
    }

    public function testDeleteKanbanProjectSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDeleteKanbanProjectSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM KanbanProject', $sql);
        $this->assertStringContainsString('Id = :id', $sql);
        $this->assertStringContainsString('IdPerson = :idPerson', $sql);
    }

    public function testGetProjectCardsSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetProjectCardsSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('WITH LastStatus AS', $sql);
        $this->assertStringContainsString('ROW_NUMBER() OVER', $sql);
        $this->assertStringContainsString('PARTITION BY kcs.IdKanbanCard', $sql);
        $this->assertStringContainsString('FROM KanbanCard kc', $sql);
        $this->assertStringContainsString('JOIN KanbanCardType kct', $sql);
        $this->assertStringContainsString('JOIN LastStatus ls', $sql);
        $this->assertStringContainsString('kct.IdKanbanProject = :idProject', $sql);
        $this->assertStringContainsString('CurrentStatus', $sql);
        $this->assertStringContainsString('ORDER BY ls.LastUpdate DESC', $sql);
    }

    public function testUpdateKanbanProjectSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUpdateKanbanProjectSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE KanbanProject', $sql);
        $this->assertStringContainsString('SET Title = :title, Detail = :detail', $sql);
        $this->assertStringContainsString('Id = :id AND IdPerson = :personId', $sql);
    }

    public function testUserHasAccessToProjectSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUserHasAccessToProjectSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('SELECT COUNT(*) as Count', $sql);
        $this->assertStringContainsString('FROM KanbanProject', $sql);
        $this->assertStringContainsString('Id = :idProject AND IdPerson = :idPerson', $sql);
    }

    public function testCreateKanbanCardTypeSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getCreateKanbanCardTypeSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO KanbanCardType', $sql);
        $this->assertStringContainsString('IdKanbanProject', $sql);
        $this->assertStringContainsString('Label', $sql);
        $this->assertStringContainsString('Detail', $sql);
        $this->assertStringContainsString('Color', $sql);
    }

    public function testDeleteKanbanCardTypeSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getDeleteKanbanCardTypeSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('DELETE FROM KanbanCardType', $sql);
        $this->assertStringContainsString('WHERE Id = :id', $sql);
    }

    public function testGetProjectCardTypesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getGetProjectCardTypesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM KanbanCardType', $sql);
        $this->assertStringContainsString('IdKanbanProject = :idProject', $sql);
        $this->assertStringContainsString('ORDER BY Detail', $sql);
    }

    public function testUpdateKanbanCardTypeSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getUpdateKanbanCardTypeSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('UPDATE KanbanCardType', $sql);
        $this->assertStringContainsString('SET Label = :label, Detail = :detail, Color = :color', $sql);
        $this->assertStringContainsString('WHERE Id = :idKanbanCardType', $sql);
    }

    private function getCreateKanbanCardSql(): string
    {
        return "
            INSERT INTO KanbanCard (IdKanbanCardType, Title, Detail) 
            VALUES (:idKanbanCardType, :title, :detail)
";
    }

    private function getDeleteKanbanCardStatusSql(): string
    {
        return "DELETE FROM KanbanCardStatus WHERE IdKanbanCard = :idKanbanCard";
    }

    private function getDeleteKanbanCardSql(): string
    {
        return "
                DELETE FROM KanbanCard
                WHERE Id IN (
                    SELECT kc.Id
                    FROM KanbanCard kc
                    JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
                    JOIN KanbanProject kp ON kp.Id = kct.IdKanbanProject
                    WHERE kc.Id = :idKanbanCard
                    AND kp.IdPerson = :idPerson)";
    }

    private function getGetKanbanProjectSql(): string
    {
        return "
            SELECT 
                Id, 
                Title, 
                Detail
            FROM KanbanProject
            WHERE Id = :id";
    }

    private function getGetKanbanProjectsSql(): string
    {
        return "
            SELECT 
                Id, 
                Title, 
                Detail
            FROM KanbanProject
            ORDER BY Title";
    }

    private function getMoveKanbanCardSql(): string
    {
        return "
            INSERT INTO KanbanCardStatus (IdKanbanCard, What, Remark, LastUpdate) 
            VALUES (:idKanbanCard, :what, :remark, :lastUpdate)
";
    }

    private function getUpdateKanbanCardSql(): string
    {
        return "
            UPDATE KanbanCard
            SET Title = :title, Detail = :detail
            WHERE Id IN (
                SELECT kc.Id
                FROM KanbanCard kc
                JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
                JOIN KanbanProject kp ON kp.Id = kct.IdKanbanProject
                WHERE kc.Id = :idKanbanCard
                AND kp.IdPerson = :idPerson
            )
";
    }

    private function getUpdateKanbanCardStatusSql(): string
    {
        return "
            UPDATE KanbanCardStatus
            SET Remark = :remark, LastUpdate = :lastUpdate
            WHERE Id IN (
                SELECT kcs.Id
                FROM KanbanCardStatus kcs
                JOIN KanbanCard kc ON kc.Id = kcs.IdKanbanCard
                JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
                JOIN KanbanProject kp ON kp.Id = kct.IdKanbanProject
                WHERE kcs.Id = :idKanbanCardStatus
                AND kp.IdPerson = :idPerson
            )
";
    }

    private function getGetKanbanHistorySql(): string
    {
        return "
            SELECT
                Id,
                CASE 
                    WHEN What = 'Created' THEN '💡'
                    WHEN What LIKE '%ToBacklog%' THEN '💡'
                    WHEN What LIKE '%ToSelected%' THEN '☑️'
                    WHEN What LIKE '%ToInProgress%' THEN '🔧'
                    WHEN What LIKE '%ToDone%' THEN '🏁'
                    ELSE '💡'
                END AS Status,
                Remark,
                LastUpdate
            FROM KanbanCardStatus
            WHERE IdKanbanCard = :idKanbanCard
            ORDER BY Id
";
    }

    private function getCreateKanbanProjectSql(): string
    {
        return "
            INSERT INTO KanbanProject (IdPerson, Title, Detail) 
            VALUES (:idPerson, :title, :detail)
";
    }

    private function getDeleteKanbanProjectSql(): string
    {
        return "DELETE FROM KanbanProject WHERE Id = :id  AND IdPerson = :idPerson";
    }

    private function getGetProjectCardsSql(): string
    {
        return "
            WITH LastStatus AS (
                SELECT
                    kcs.*,
                    ROW_NUMBER() OVER (
                    PARTITION BY kcs.IdKanbanCard
                        ORDER BY kcs.Id DESC
                    ) AS rn
                FROM KanbanCardStatus kcs
            )
            SELECT
                kc.Id,
                kc.Title,
                kc.Detail,
                kc.IdKanbanCardType,
                kct.Label,
                CASE 
                    WHEN ls.What = 'Created' THEN '💡'
                    WHEN ls.What LIKE '%ToBacklog%' THEN '💡'
                    WHEN ls.What LIKE '%ToSelected%' THEN '☑️'
                    WHEN ls.What LIKE '%ToInProgress%' THEN '🔧'
                    WHEN ls.What LIKE '%ToDone%' THEN '🏁'
                    ELSE '💡'
                END AS CurrentStatus
            FROM KanbanCard kc
            JOIN KanbanCardType kct ON kct.Id = kc.IdKanbanCardType
            JOIN LastStatus ls ON ls.IdKanbanCard = kc.Id AND ls.rn = 1
            WHERE kct.IdKanbanProject = :idProject
            ORDER BY ls.LastUpdate DESC;
";
    }

    private function getUpdateKanbanProjectSql(): string
    {
        return "
            UPDATE KanbanProject 
            SET Title = :title, Detail = :detail 
            WHERE Id = :id AND IdPerson = :personId
";
    }

    private function getUserHasAccessToProjectSql(): string
    {
        return "
            SELECT COUNT(*) as Count
            FROM KanbanProject
            WHERE Id = :idProject AND IdPerson = :idPerson
";
    }

    private function getCreateKanbanCardTypeSql(): string
    {
        return "
            INSERT INTO KanbanCardType (IdKanbanProject, Label, Detail, Color) 
            VALUES (:idKanbanProject, :label, :detail, :color)
";
    }

    private function getDeleteKanbanCardTypeSql(): string
    {
        return "DELETE FROM KanbanCardType WHERE Id = :id";
    }

    private function getGetProjectCardTypesSql(): string
    {
        return "
            SELECT 
                Id,
                Label,
                Detail,
                Color
            FROM KanbanCardType
            WHERE IdKanbanProject = :idProject
            ORDER BY Detail 
";
    }

    private function getUpdateKanbanCardTypeSql(): string
    {
        return "
            UPDATE KanbanCardType 
            SET Label = :label, Detail = :detail, Color = :color
            WHERE Id = :idKanbanCardType
";
    }
}