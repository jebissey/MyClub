<?php

declare(strict_types=1);

namespace tests\models;

use Closure;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use app\helpers\Application;
use app\models\ArticleDataHelper;
use app\models\AuthorizationDataHelper;
use app\modules\Article\valueObjects\ArticleAuthorRow;
use app\modules\Article\valueObjects\ArticleRow;
use app\modules\Article\valueObjects\ArticleRssRow;
use app\modules\Article\valueObjects\ArticleSummaryRow;

/**
 * NOTE: getNews() and the getSpotlightArticle()/isSpotlightActive()/
 * setSpotlightArticle() family are not covered here.
 * - getNews() instantiates its own AuthorizationDataHelper internally
 *   (not injectable) and delegates to AuthorizationDataHelper::getArticle(),
 *   which is out of reach without a further DI refactor.
 * - The spotlight methods rely on Data::get()/Data::set(), inherited from
 *   the Data base class, whose internals aren't visible here.
 * Both are left for a later pass, same spirit as ConnectedUserTest's NOTE.
 *
 * Everything else either has no DB dependency (calculateTotals) or only
 * touches $this->pdo / the injected AuthorizationDataHelper. ArticleDataHelper
 * isn't built for DI (unlike ConnectedUser), so instances here are built via
 * newInstanceWithoutConstructor() + direct property injection (pdo,
 * application, authorizationDataHelper), bypassing Data's real constructor
 * entirely rather than guessing its internals.
 *
 * Each DB-touching method is exercised two ways:
 *  - against a small in-memory SQLite fixture with a controlled schema and
 *    seeded rows, for deterministic behavioural assertions;
 *  - against the real shipped MyClub.sqlite template, to confirm every
 *    referenced table/column actually exists and the raw SQL is valid.
 */
final class ArticleDataHelperTest extends TestCase
{
    private const DB_PATH = __DIR__ . '/../../app/models/database/MyClub.sqlite';

    // --- test doubles / wiring helpers ---

    private function makeHelper(
        PDO $pdo,
        ?AuthorizationDataHelper $authorizationDataHelper = null,
        ?Application $application = null,
    ): ArticleDataHelper {
        /** @var ArticleDataHelper $helper */
        $helper = (new ReflectionClass(ArticleDataHelper::class))->newInstanceWithoutConstructor();

        $this->setProperty($helper, 'pdo', $pdo);
        $this->setProperty($helper, 'application', $application ?? $this->createStub(Application::class));
        $this->setProperty(
            $helper,
            'authorizationDataHelper',
            $authorizationDataHelper ?? $this->createStub(AuthorizationDataHelper::class),
        );

        return $helper;
    }

    private function makeHelperWithoutDb(): ArticleDataHelper
    {
        /** @var ArticleDataHelper $helper */
        $helper = (new ReflectionClass(ArticleDataHelper::class))->newInstanceWithoutConstructor();

        return $helper;
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        Closure::bind(function () use ($property, $value): void {
            $this->$property = $value;
        }, $object, ArticleDataHelper::class)();
    }

    /** @param array<int, mixed> $args */
    private function invokePrivate(object $object, string $method, array $args = []): mixed
    {
        $reflectionMethod = new ReflectionMethod($object, $method);
        return $reflectionMethod->invokeArgs($object, $args);
    }

    // --- fixture database: controlled schema + data, for behavioural tests ---

    private function createFixtureDatabase(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec('
            CREATE TABLE Article (
                Id INTEGER PRIMARY KEY,
                Title TEXT,
                Content TEXT,
                LastUpdate TEXT,
                Timestamp TEXT,
                CreatedBy INTEGER,
                IdGroup INTEGER,
                OnlyForMembers INTEGER,
                PublishedBy INTEGER
            )
        ');
        $pdo->exec('
            CREATE TABLE Person (
                Id INTEGER PRIMARY KEY,
                FirstName TEXT,
                LastName TEXT,
                NickName TEXT
            )
        ');
        $pdo->exec('
            CREATE TABLE "Group" (
                Id INTEGER PRIMARY KEY,
                Name TEXT
            )
        ');
        $pdo->exec('
            CREATE TABLE Settings (
                Name TEXT,
                Value TEXT
            )
        ');
        $pdo->exec('
            CREATE TABLE MenuItem (
                ForAnonymous INTEGER,
                Url TEXT
            )
        ');
        $pdo->exec('
            CREATE TABLE Carousel (
                IdArticle INTEGER,
                Item TEXT
            )
        ');

        return $pdo;
    }

    /** @param array<string, mixed> $overrides */
    private function insertArticle(PDO $pdo, array $overrides): void
    {
        $defaults = [
            'Id'             => null,
            'Title'          => 'Untitled',
            'Content'        => '',
            'LastUpdate'     => '2026-01-01 00:00:00',
            'Timestamp'      => '2026-01-01 00:00:00',
            'CreatedBy'      => null,
            'IdGroup'        => null,
            'OnlyForMembers' => 0,
            'PublishedBy'    => null,
        ];
        $row = array_merge($defaults, $overrides);

        $stmt = $pdo->prepare('
            INSERT INTO Article (Id, Title, Content, LastUpdate, Timestamp, CreatedBy, IdGroup, OnlyForMembers, PublishedBy)
            VALUES (:Id, :Title, :Content, :LastUpdate, :Timestamp, :CreatedBy, :IdGroup, :OnlyForMembers, :PublishedBy)
        ');
        $stmt->execute(array_combine(
            array_map(static fn(string $key): string => ":$key", array_keys($row)),
            $row,
        ));
    }

    private function insertPerson(PDO $pdo, int $id, string $firstName, string $lastName, string $nickName = ''): void
    {
        $stmt = $pdo->prepare('INSERT INTO Person (Id, FirstName, LastName, NickName) VALUES (:id, :first, :last, :nick)');
        $stmt->execute([':id' => $id, ':first' => $firstName, ':last' => $lastName, ':nick' => $nickName]);
    }

    private function insertGroup(PDO $pdo, int $id, string $name): void
    {
        $stmt = $pdo->prepare('INSERT INTO "Group" (Id, Name) VALUES (:id, :name)');
        $stmt->execute([':id' => $id, ':name' => $name]);
    }

    // --- calculateTotals(): pure logic, no DB ---

    public function testCalculateTotalsSumsCountsByAuthorAndAudience(): void
    {
        $helper = $this->makeHelperWithoutDb();

        $crosstabData = [
            'authors'   => [(object) ['Id' => 1], (object) ['Id' => 2]],
            'audiences' => [['id' => 10], ['id' => 20]],
            'data'      => [10 => [1 => 3, 2 => 5], 20 => [1 => 2]],
        ];

        $this->assertSame(
            ['byAuthor' => [1 => 5, 2 => 5], 'byAudience' => [10 => 8, 20 => 2]],
            $helper->calculateTotals($crosstabData),
        );
    }

    public function testCalculateTotalsHandlesEmptyData(): void
    {
        $helper = $this->makeHelperWithoutDb();

        $this->assertSame(
            ['byAuthor' => [], 'byAudience' => []],
            $helper->calculateTotals(['authors' => [], 'audiences' => [], 'data' => []]),
        );
    }

    public function testCalculateTotalsTreatsMissingCellsAsZero(): void
    {
        $helper = $this->makeHelperWithoutDb();

        $crosstabData = [
            'authors'   => [(object) ['Id' => 1]],
            'audiences' => [['id' => 10]],
            'data'      => [],
        ];

        $this->assertSame(
            ['byAuthor' => [1 => 0], 'byAudience' => [10 => 0]],
            $helper->calculateTotals($crosstabData),
        );
    }

    // --- access-control id helpers (fixture DB) ---

    public function testGetNoGroupArticleIdsReturnsOnlyPublishedUngroupedNonMemberArticles(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertArticle($pdo, ['Id' => 1, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 0]);
        $this->insertArticle($pdo, ['Id' => 2, 'PublishedBy' => null, 'IdGroup' => null, 'OnlyForMembers' => 0]);
        $this->insertArticle($pdo, ['Id' => 3, 'PublishedBy' => 1, 'IdGroup' => 5, 'OnlyForMembers' => 0]);
        $this->insertArticle($pdo, ['Id' => 4, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 1]);

        $helper = $this->makeHelper($pdo);

        $this->assertSame([1], $this->invokePrivate($helper, 'getNoGroupArticleIds'));
    }

    public function testGetArticleIdsForMembersReturnsOnlyPublishedUngroupedMemberArticles(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertArticle($pdo, ['Id' => 1, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 1]);
        $this->insertArticle($pdo, ['Id' => 2, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 0]);

        $helper = $this->makeHelper($pdo);

        $this->assertSame([1], $this->invokePrivate($helper, 'getArticleIdsForMembers'));
    }

    public function testGetArticleIdsByGroupsReturnsPublishedArticlesInGivenGroups(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertArticle($pdo, ['Id' => 1, 'PublishedBy' => 1, 'IdGroup' => 5]);
        $this->insertArticle($pdo, ['Id' => 2, 'PublishedBy' => 1, 'IdGroup' => 6]);
        $this->insertArticle($pdo, ['Id' => 3, 'PublishedBy' => null, 'IdGroup' => 5]);

        $helper = $this->makeHelper($pdo);

        $this->assertSame([1], $this->invokePrivate($helper, 'getArticleIdsByGroups', [[5]]));
        $this->assertSame([], $this->invokePrivate($helper, 'getArticleIdsByGroups', [[]]));
    }

    public function testGetArticleIdsBasedOnAccessReturnsNoGroupArticlesWhenEmailIsEmpty(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertArticle($pdo, ['Id' => 1, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 0]);

        $helper = $this->makeHelper($pdo);

        $this->assertSame([1], $this->invokePrivate($helper, 'getArticleIdsBasedOnAccess', [null]));
    }

    public function testGetArticleIdsBasedOnAccessMergesGroupArticlesForKnownUser(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertArticle($pdo, ['Id' => 1, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 0]);
        $this->insertArticle($pdo, ['Id' => 2, 'PublishedBy' => 1, 'IdGroup' => 5, 'OnlyForMembers' => 0]);

        /** @var AuthorizationDataHelper&\PHPUnit\Framework\MockObject\MockObject $authorizationDataHelper */
        $authorizationDataHelper = $this->createMock(AuthorizationDataHelper::class);

        $authorizationDataHelper
            ->expects($this->once())
            ->method('getUserGroups')
            ->with('member@example.com')
            ->willReturn([5]);

        $helper = $this->makeHelper($pdo, $authorizationDataHelper);

        $result = $this->invokePrivate($helper, 'getArticleIdsBasedOnAccess', ['member@example.com']);
        sort($result);

        $this->assertSame([1, 2], $result);
    }

    public function testIsUserAllowedToReadArticleReflectsAccessList(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertArticle($pdo, ['Id' => 1, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 0]);

        $helper = $this->makeHelper($pdo);

        $this->assertTrue($helper->isUserAllowedToReadArticle('', 1));
        $this->assertFalse($helper->isUserAllowedToReadArticle('', 999));
    }

    // --- article retrieval methods (fixture DB) ---

    public function testGetWithAuthorReturnsArticleRowWithAuthorNames(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertPerson($pdo, 7, 'Jean', 'Dupont');
        $this->insertArticle($pdo, ['Id' => 1, 'CreatedBy' => 7, 'Title' => 'Test']);

        $helper = $this->makeHelper($pdo);
        $article = $helper->getWithAuthor(1);

        $this->assertInstanceOf(ArticleRow::class, $article);
        $this->assertSame('Jean', $article->Author?->FirstName);
        $this->assertSame('Dupont', $article->Author?->LastName);
    }

    public function testGetWithAuthorReturnsFalseWhenArticleDoesNotExist(): void
    {
        $helper = $this->makeHelper($this->createFixtureDatabase());

        $this->assertFalse($helper->getWithAuthor(999));
    }

    public function testGetLatestArticleReturnsMostRecentlyUpdatedArticle(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertPerson($pdo, 1, 'Jean', 'Dupont');
        $this->insertGroup($pdo, 5, 'Bureau');
        $this->insertArticle($pdo, ['Id' => 1, 'CreatedBy' => 1, 'IdGroup' => 5, 'LastUpdate' => '2026-01-01 00:00:00']);
        $this->insertArticle($pdo, ['Id' => 2, 'CreatedBy' => 1, 'IdGroup' => 5, 'LastUpdate' => '2026-06-01 00:00:00']);

        $helper = $this->makeHelper($pdo);
        $latest = $helper->getLatestArticle([1, 2]);

        $this->assertInstanceOf(ArticleRow::class, $latest);
        $this->assertSame(2, $latest->Id);
    }

    public function testGetLatestArticleReturnsNullForEmptyIdList(): void
    {
        $helper = $this->makeHelper($this->createFixtureDatabase());

        $this->assertNull($helper->getLatestArticle([]));
    }

    public function testGetAuthorsByArticleIdsIndexesResultsByArticleId(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertPerson($pdo, 1, 'Jean', 'Dupont');
        $this->insertPerson($pdo, 2, 'Marie', 'Martin', 'Mimi');
        $this->insertArticle($pdo, ['Id' => 10, 'CreatedBy' => 1, 'Title' => 'A']);
        $this->insertArticle($pdo, ['Id' => 20, 'CreatedBy' => 2, 'Title' => 'B']);

        $helper = $this->makeHelper($pdo);
        $result = $helper->getAuthorsByArticleIds([10, 20]);

        $this->assertContainsOnlyInstancesOf(ArticleAuthorRow::class, $result);
        $this->assertSame('Jean Dupont', $result[10]->PersonName);
        $this->assertSame('Marie Martin (Mimi)', $result[20]->PersonName);
    }

    public function testGetAuthorsByArticleIdsReturnsEmptyArrayForEmptyInput(): void
    {
        $helper = $this->makeHelper($this->createFixtureDatabase());

        $this->assertSame([], $helper->getAuthorsByArticleIds([]));
    }

    public function testGetArticlesForRssReturnsOnlyPublishedArticlesOrderedByLastUpdateDesc(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertArticle($pdo, [
            'Id' => 1,
            'PublishedBy' => 1,
            'LastUpdate' => '2026-01-01 00:00:00',
            'Timestamp' => '2026-01-01 00:00:00',
        ]);
        $this->insertArticle($pdo, [
            'Id' => 2,
            'PublishedBy' => 1,
            'LastUpdate' => '2026-06-01 00:00:00',
            'Timestamp' => '2026-06-01 00:00:00',
        ]);
        $this->insertArticle($pdo, ['Id' => 3, 'PublishedBy' => null, 'LastUpdate' => '2026-12-01 00:00:00']);

        $helper = $this->makeHelper($pdo);
        $result = $helper->getArticlesForRss();

        $this->assertContainsOnlyInstancesOf(ArticleRssRow::class, $result);
        $this->assertSame(2, $result[0]->Id);
        $this->assertSame(1, $result[1]->Id);
    }

    public function testGetArticlesForAllClassifiesReferenceSource(): void
    {
        $pdo = $this->createFixtureDatabase();

        $this->insertArticle($pdo, ['Id' => 1, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 0]);
        $pdo->exec("INSERT INTO Settings (Name, Value) VALUES ('Home_FeaturedArticleId', '1')");

        $this->insertArticle($pdo, ['Id' => 2, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 0]);
        $pdo->exec("INSERT INTO Settings (Name, Value) VALUES ('Home_FooterArticleId', '2')");

        $this->insertArticle($pdo, ['Id' => 3, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 1]);
        $pdo->exec("INSERT INTO MenuItem (ForAnonymous, Url) VALUES (1, '/menu/show/article/3')");

        $this->insertArticle($pdo, ['Id' => 4, 'PublishedBy' => 1, 'IdGroup' => null, 'OnlyForMembers' => 0]);

        $helper = $this->makeHelper($pdo);

        $byId = [];
        foreach ($helper->getArticlesForAll() as $row) {
            $byId[$row->Id] = $row->ReferenceSource;
        }

        $this->assertSame('Home_Featured', $byId[1]);
        $this->assertSame('Home_Footer', $byId[2]);
        $this->assertSame('Menu', $byId[3]);
        $this->assertSame('Public', $byId[4]);
    }

    public function testInArticlesFindsMatchesInContentAndCarousel(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertArticle($pdo, ['Id' => 1, 'Content' => 'See /media/photo.jpg here']);
        $this->insertArticle($pdo, ['Id' => 2, 'Content' => 'Nothing relevant']);
        $pdo->exec("INSERT INTO Carousel (IdArticle, Item) VALUES (2, '/media/photo.jpg')");

        $helper = $this->makeHelper($pdo);

        $ids = array_map(static fn(object $row): int => (int) $row->Id, $helper->inArticles('/media/photo.jpg'));
        sort($ids);

        $this->assertSame([1, 2], $ids);
    }

    public function testGetPathsUsedInArticlesFlagsPathsFoundInContent(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertArticle($pdo, ['Id' => 1, 'Content' => 'uses data/media/2026/01/photo.jpg inline']);
        $this->insertArticle($pdo, ['Id' => 2, 'Content' => 'no matches here']);

        $helper = $this->makeHelper($pdo);

        $this->assertSame(
            ['data/media/2026/01/photo.jpg' => true],
            $helper->getPathsUsedInArticles(['data/media/2026/01/photo.jpg', 'data/media/unused.png']),
        );
    }

    public function testGetPathsUsedInArticlesReturnsEmptyArrayForEmptyInput(): void
    {
        $helper = $this->makeHelper($this->createFixtureDatabase());

        $this->assertSame([], $helper->getPathsUsedInArticles([]));
    }

    public function testGetLatestArticlesReturnsLatestArticleAndSummaryList(): void
    {
        $pdo = $this->createFixtureDatabase();
        $this->insertPerson($pdo, 1, 'Jean', 'Dupont');
        $this->insertArticle($pdo, ['Id' => 1, 'CreatedBy' => 1, 'PublishedBy' => 1, 'LastUpdate' => '2026-01-01 00:00:00']);
        $this->insertArticle($pdo, ['Id' => 2, 'CreatedBy' => 1, 'PublishedBy' => 1, 'LastUpdate' => '2026-06-01 00:00:00']);

        $helper = $this->makeHelper($pdo);
        $result = $helper->getLatestArticles(null, 5);

        $this->assertInstanceOf(ArticleRow::class, $result['latestArticle']);
        $this->assertSame(2, $result['latestArticle']->Id);
        $this->assertCount(2, $result['latestArticles']);
        $this->assertContainsOnlyInstancesOf(ArticleSummaryRow::class, $result['latestArticles']);
    }

    public function testGetLatestArticlesReturnsEmptyShapeWhenNoAccessibleArticles(): void
    {
        $helper = $this->makeHelper($this->createFixtureDatabase());

        $this->assertSame(
            ['latestArticle' => null, 'latestArticles' => []],
            $helper->getLatestArticles(null, 5),
        );
    }

    // --- schema validation against the real shipped template ---

    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openTemplateDatabaseOrSkip();

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'Title',
            'Content',
            'LastUpdate',
            'Timestamp',
            'CreatedBy',
            'IdGroup',
            'OnlyForMembers',
            'PublishedBy',
        ]);
        $this->assertColumnsExist($pdo, 'Person', ['Id', 'FirstName', 'LastName', 'NickName']);
        $this->assertColumnsExist($pdo, 'Group', ['Id', 'Name']);
        $this->assertColumnsExist($pdo, 'Settings', ['Name', 'Value']);
        $this->assertColumnsExist($pdo, 'MenuItem', ['ForAnonymous', 'Url']);
        $this->assertColumnsExist($pdo, 'Carousel', ['IdArticle', 'Item']);
    }

    public function testRawSqlMethodsExecuteAgainstTemplateSchema(): void
    {
        $pdo = $this->openTemplateDatabaseOrSkip();
        $helper = $this->makeHelper($pdo);

        $this->assertIsArray($helper->getArticlesForAll());
        $this->assertIsArray($helper->getArticlesForRss());
        $this->assertIsArray($helper->getAuthorsByArticleIds([1, 2, 3]));
        $this->assertIsArray($helper->inArticles('/data/media/'));
        $this->assertIsArray($helper->getPathsUsedInArticles(['data/media/does-not-exist.jpg']));

        $withAuthor = $helper->getWithAuthor(999999999);
        $this->assertTrue($withAuthor === false || $withAuthor instanceof ArticleRow);

        $latest = $helper->getLatestArticle([1, 2, 3]);
        $this->assertTrue($latest === null || $latest instanceof ArticleRow);

        $this->assertIsArray($this->invokePrivate($helper, 'getNoGroupArticleIds'));
        $this->assertIsArray($this->invokePrivate($helper, 'getArticleIdsForMembers'));
        $this->assertIsArray($this->invokePrivate($helper, 'getArticleIdsByGroups', [[1, 2]]));
        $this->assertIsArray($this->invokePrivate($helper, 'doGetLatestArticles', [[1, 2, 3], 5]));
    }

    /** @param array<int, string> $expectedColumns */
    private function assertColumnsExist(PDO $pdo, string $table, array $expectedColumns): void
    {
        $stmt = $pdo->query(sprintf('PRAGMA table_info("%s")', $table));
        $this->assertNotFalse($stmt, "Could not read schema for table '{$table}'.");

        $actualColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
        $this->assertNotEmpty($actualColumns, "Table '{$table}' does not exist in the template database.");

        foreach ($expectedColumns as $column) {
            $this->assertContains(
                $column,
                $actualColumns,
                "Expected column '{$table}.{$column}' not found in the template database schema.",
            );
        }
    }

    private function openTemplateDatabaseOrSkip(): PDO
    {
        if (!file_exists(self::DB_PATH)) {
            $this->markTestSkipped('Template database not found at ' . self::DB_PATH);
        }

        $pdo = new PDO('sqlite:' . self::DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
