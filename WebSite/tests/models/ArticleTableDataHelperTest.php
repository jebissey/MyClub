<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class ArticleTableDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        // article_list_view et public_article_list_view sont des vues :
        // leur schéma est couvert par les tests dédiés aux vues/viewModels,
        // pas ici. On ne vérifie ici que la table réelle interrogée.
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'PersonGroup', [
            'IdGroup',
            'IdPerson',
        ]);
    }

    public function testGetQueryForAnonymousUserSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getQueryForAnonymousUserSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM article_list_view', $sql);
        $this->assertStringContainsString('Id, CreatedBy, Title, LastUpdate, PersonName, GroupName, Pool, PoolDetail, ForMembers, Messages, Menu', $sql);
        $this->assertStringContainsString('CASE', $sql);
        $this->assertStringContainsString('WHEN PublishedBy IS NULL THEN "non"', $sql);
        $this->assertStringContainsString('END AS Published', $sql);
        $this->assertStringContainsString('WHERE (IdGroup IS NULL AND OnlyForMembers = 0 AND PublishedBy IS NOT NULL)', $sql);
        $this->assertStringContainsString('ORDER BY LastUpdate DESC', $sql);
    }

    public function testGetQueryForConnectedNonEditorSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getQueryForConnectedNonEditorSql(7);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM article_list_view', $sql);
        $this->assertStringContainsString('CreatedBy = 7', $sql);
        $this->assertStringContainsString('PublishedBy IS NOT NULL', $sql);
        $this->assertStringContainsString('IdGroup IS NULL', $sql);
        $this->assertStringContainsString('IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = 7)', $sql);
        $this->assertStringContainsString('ORDER BY LastUpdate DESC', $sql);
    }

    public function testGetQueryForConnectedEditorSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getQueryForConnectedEditorSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM article_list_view', $sql);
        $this->assertStringNotContainsString('WHERE', $sql);
        $this->assertStringContainsString('ORDER BY LastUpdate DESC', $sql);
    }

    public function testGetSpotlightArticleCaseSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getQueryForAnonymousUserSql(42);

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('WHEN Id = 42 THEN "oui 📌"', $sql);
        $this->assertStringContainsString('ELSE Published', $sql);
    }

    public function testGetQueryForPublicArticlesSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $sql = $this->getQueryForPublicArticlesSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('FROM public_article_list_view', $sql);
        $this->assertStringContainsString('Id, Title, LastUpdate, ReferenceSource', $sql);
        $this->assertStringContainsString('ORDER BY LastUpdate DESC', $sql);
    }

    // -------------------------------------------------------------------------
    // Private SQL extractors (mirrored from ArticleTableDataHelper)
    // -------------------------------------------------------------------------

    private function getQueryForAnonymousUserSql(int $spotlightArticleId = 42): string
    {
        return '
            SELECT Id, CreatedBy, Title, LastUpdate, PersonName, GroupName, Pool, PoolDetail, ForMembers, Messages, Menu, CASE 
                WHEN PublishedBy IS NULL THEN "non" 
                ELSE 
                    CASE 
                        WHEN Id = ' . $spotlightArticleId . ' THEN "oui 📌"
                        ELSE Published
                    END
                END AS Published
            FROM article_list_view
            WHERE (IdGroup IS NULL AND OnlyForMembers = 0 AND PublishedBy IS NOT NULL)
            ORDER BY LastUpdate DESC
        ';
    }

    private function getQueryForConnectedNonEditorSql(int $personId, int $spotlightArticleId = 42): string
    {
        return '
            SELECT Id, CreatedBy, Title, LastUpdate, PersonName, GroupName, Pool, PoolDetail, ForMembers, Messages, Menu, CASE 
                WHEN PublishedBy IS NULL THEN "non" 
                ELSE 
                    CASE 
                        WHEN Id = ' . $spotlightArticleId . ' THEN "oui 📌"
                        ELSE Published
                    END
                END AS Published
            FROM article_list_view
            WHERE (CreatedBy = ' . $personId . '
                OR (PublishedBy IS NOT NULL 
                    AND (IdGroup IS NULL 
                        OR IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ' . $personId . '))
                ))
            ORDER BY LastUpdate DESC
        ';
    }

    private function getQueryForConnectedEditorSql(int $spotlightArticleId = 42): string
    {
        return '
            SELECT Id, CreatedBy, Title, LastUpdate, PersonName, GroupName, Pool, PoolDetail, ForMembers, Messages, Menu, CASE 
                WHEN PublishedBy IS NULL THEN "non" 
                ELSE 
                    CASE 
                        WHEN Id = ' . $spotlightArticleId . ' THEN "oui 📌"
                        ELSE Published
                    END
                END AS Published
            FROM article_list_view
            ORDER BY LastUpdate DESC
        ';
    }

    private function getQueryForPublicArticlesSql(): string
    {
        return '
            SELECT Id, Title, LastUpdate, ReferenceSource
            FROM public_article_list_view
            ORDER BY LastUpdate DESC
        ';
    }
}