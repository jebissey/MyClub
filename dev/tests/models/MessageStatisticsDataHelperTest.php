<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class MessageStatisticsDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Message columns read/filtered on in countUserMessagesForArticle()
        // and countByEmailForSeason()
        $this->assertColumnsExist($pdo, 'Message', [
            'Id',
            'PersonId',
            'From',
            'ArticleId',
            'EventId',
            'LastUpdate',
        ]);

        // Member and Individual columns joined/read in countByEmailForSeason()
        $this->assertColumnsExist($pdo, 'Member', ['Id']);
        $this->assertColumnsExist($pdo, 'Individual', ['Id', 'Email']);
    }

    public function testCountUserMessagesForArticleSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = 'SELECT COUNT(*) FROM Message WHERE "From" = :from AND ArticleId = :articleId';

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testCountByEmailForSeasonSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $sql = "
            SELECT LOWER(i.Email) AS Email, COUNT(m.Id) AS MessageCount
            FROM Message m
            JOIN Member     mb ON mb.Id = m.PersonId
            JOIN Individual i  ON i.Id  = mb.Id
            WHERE m.LastUpdate BETWEEN :start AND :end
            GROUP BY i.Email
        ";

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }
}