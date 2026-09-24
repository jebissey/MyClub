<?php

declare(strict_types=1);

namespace tests\models;

use PDOStatement;

class LogWriterDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);

        $this->assertColumnsExist($pdo, 'Log', [
            'IpAddress',
            'Referer',
            'Os',
            'Browser',
            'ScreenResolution',
            'Type',
            'Uri',
            'Token',
            'Who',
            'Code',
            'Message',
            'Duration',
            'CreatedAt',
        ]);
    }

    public function testAddSqlIsValidAgainstTemplateSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getAddSql();

        $stmt = $pdo->prepare($sql);
        $this->assertInstanceOf(PDOStatement::class, $stmt);

        $this->assertStringContainsString('INSERT INTO Log (', $sql);
        $this->assertStringContainsString(
            'IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token,',
            $sql,
        );
        $this->assertStringContainsString('Who, Code, Message, Duration, CreatedAt', $sql);
        $this->assertStringContainsString('VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,', $sql);
        $this->assertStringContainsString("strftime('%Y-%m-%d %H:%M:%f', 'now')", $sql);
    }

    public function testAddInsertsAndReturnsLastInsertId(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip(self::LOG_DB_PATH);
        $sql = $this->getAddSql();

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            '127.0.0.1',
            'https://example.org',
            'Linux',
            'Firefox',
            '1920x1080',
            'page',
            '/home',
            'token-abc',
            'someone@example.org',
            '200',
            'ok',
            0.42,
        ]);

        $this->assertSame(1, (int) $pdo->lastInsertId());

        $row = $pdo->query('SELECT IpAddress, Who, Code, Message FROM Log WHERE Id = 1')
            ->fetch();

        $this->assertSame('127.0.0.1', $row['IpAddress']);
        $this->assertSame('someone@example.org', $row['Who']);
        $this->assertSame('200', $row['Code']);
        $this->assertSame('ok', $row['Message']);
    }

    private function getAddSql(): string
    {
        return "
            INSERT INTO Log (
            IpAddress, Referer, Os, Browser, ScreenResolution, Type, Uri, Token,
            Who, Code, Message, Duration, CreatedAt
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, strftime('%Y-%m-%d %H:%M:%f', 'now'))
        ";
    }
}