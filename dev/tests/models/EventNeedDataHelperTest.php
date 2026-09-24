<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class EventNeedDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'EventNeed', [
            'Id',
            'IdEvent',
            'IdNeed',
        ]);

        $this->assertColumnsExist($pdo, 'Need', [
            'Id',
            'Label',
            'Name',
            'ParticipantDependent',
            'IdNeedType',
        ]);

        $this->assertColumnsExist($pdo, 'NeedType', [
            'Id',
            'Name',
        ]);
    }

    public function testNeedsForEventSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getNeedsForEventSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('EventNeed.*', $sql);
        $this->assertStringContainsString('Need.Label', $sql);
        $this->assertStringContainsString('Need.Name', $sql);
        $this->assertStringContainsString('Need.ParticipantDependent', $sql);
        $this->assertStringContainsString('NeedType.Name AS TypeName', $sql);
        $this->assertStringContainsString('INNER JOIN Need', $sql);
        $this->assertStringContainsString('INNER JOIN NeedType', $sql);
        $this->assertStringContainsString('EventNeed.IdEvent = :eventId', $sql);
    }

    private function getNeedsForEventSql(): string
    {
        return "
            SELECT 
                EventNeed.*,
                Need.Label,
                Need.Name,
                Need.ParticipantDependent,
                NeedType.Name AS TypeName
            FROM EventNeed
            INNER JOIN Need ON EventNeed.IdNeed = Need.Id
            INNER JOIN NeedType ON Need.IdNeedType = NeedType.Id
            WHERE EventNeed.IdEvent = :eventId
";
    }
}