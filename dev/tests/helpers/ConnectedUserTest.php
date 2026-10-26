<?php

declare(strict_types=1);

namespace tests\helpers;

use PDO;
use ReflectionClass;
use ReflectionProperty;
use app\enums\ApplicationError;
use app\enums\Authorization;
use app\helpers\ConnectedUser;
use app\helpers\interfaces\ErrorManagerInterface;
use app\helpers\interfaces\GravatarHandlerInterface;
use app\models\AuthorizationDataHelper;
use app\models\Data;
use app\models\DataHelper;
use app\models\MetadataDataHelper;
use app\modules\Common\valueObjects\ConnectedUser as ConnectedUserVO;
use app\modules\Common\valueObjects\Person;
use tests\models\DataHelperTestCase;
use tests\support\DataHelperFactory;

/**
 * The success paths of getLastSignIn(), getLastSignOut() and get() still touch
 * static helpers (Person::fromRow, Params::*, TranslationManager::*, WebApp::*).
 * They will be tested once those helpers are injectable.
 *
 * DataHelper, AuthorizationDataHelper and MetadataDataHelper are final: real
 * instances are used, on top of in-memory SQLite databases.
 */
final class ConnectedUserTest extends DataHelperTestCase
{
    /** @var array<string, mixed> */
    private array $server;

    /** @var array<string, mixed> */
    private array $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->server  = $_SERVER;
        $this->session = $_SESSION ?? [];
    }

    protected function tearDown(): void
    {
        $_SERVER  = $this->server;
        $_SESSION = $this->session;
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers – DataHelper
    // -------------------------------------------------------------------------

    /**
     * Light real DataHelper on an in-memory SQLite database, for the tests that
     * do not need to control get()/set() precisely.
     */
    private function makeRealDataHelper(): DataHelper
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("
            CREATE TABLE Individual (
                Id INTEGER PRIMARY KEY AUTOINCREMENT,
                Type TEXT,
                FirstName TEXT,
                LastName TEXT,
                Email TEXT,
                NickName TEXT,
                Avatar TEXT
            );
            CREATE TABLE Member (
                Id INTEGER PRIMARY KEY,
                Alert TEXT,
                UseGravatar INTEGER DEFAULT 0,
                LastSignIn TEXT,
                LastSignOut TEXT
            );
            CREATE TABLE Settings (
                Id INTEGER PRIMARY KEY AUTOINCREMENT,
                Name TEXT UNIQUE,
                Value TEXT
            );
        ");

        return new DataHelper(
            $pdo,
            $this->createStub(ErrorManagerInterface::class)
        );
    }

    // -------------------------------------------------------------------------
    // Helpers – AuthorizationDataHelper
    // -------------------------------------------------------------------------

    /**
     * AuthorizationDataHelper is final, so it cannot be mocked, stubbed or subclassed.
     * The real class is used instead, on top of an in-memory SQLite database.
     *
     * The constructor is bypassed (it needs a full Application) and only the
     * inherited $pdo property is set.
     */
    private function makeAuthorizationDataHelper(?PDO $pdo = null): AuthorizationDataHelper
    {
        /** @var AuthorizationDataHelper $helper */
        $helper = (new ReflectionClass(AuthorizationDataHelper::class))
            ->newInstanceWithoutConstructor();

        (new ReflectionProperty(Data::class, 'pdo'))
            ->setValue($helper, $pdo ?? $this->makeAuthorizationSchemaPdo());

        return $helper;
    }

    /**
     * Minimal schema used by AuthorizationDataHelper::getsFor(), taken from the real query.
     */
    private function makeAuthorizationSchemaPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("
            CREATE TABLE Member (
                Id INTEGER PRIMARY KEY
            );
            CREATE TABLE MemberGroup (
                IdMember INTEGER,
                IdGroup  INTEGER
            );
            CREATE TABLE `Group` (
                Id INTEGER PRIMARY KEY
            );
            CREATE TABLE GroupAuthorization (
                IdGroup         INTEGER,
                IdAuthorization INTEGER
            );
            CREATE TABLE Authorization (
                Id   INTEGER PRIMARY KEY,
                Name TEXT
            );
        ");

        return $pdo;
    }

    /**
     * Inserts a member, a group, the member-group link, the authorizations and
     * the group-authorization links.
     *
     * @param list<string> $authorizationNames values of Authorization::*->value
     */
    private function givenAuthorizations(PDO $pdo, int $memberId, array $authorizationNames): void
    {
        $pdo->prepare('INSERT INTO Member (Id) VALUES (?)')
            ->execute([$memberId]);

        // One group per member is enough for these tests
        $groupId = 1;
        $pdo->prepare('INSERT OR IGNORE INTO `Group` (Id) VALUES (?)')
            ->execute([$groupId]);
        $pdo->prepare('INSERT INTO MemberGroup (IdMember, IdGroup) VALUES (?, ?)')
            ->execute([$memberId, $groupId]);

        foreach ($authorizationNames as $i => $name) {
            $authId = $i + 1;
            $pdo->prepare('INSERT INTO Authorization (Id, Name) VALUES (?, ?)')
                ->execute([$authId, $name]);
            $pdo->prepare('INSERT INTO GroupAuthorization (IdGroup, IdAuthorization) VALUES (?, ?)')
                ->execute([$groupId, $authId]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers – MetadataDataHelper
    // -------------------------------------------------------------------------

    /**
     * Real MetadataDataHelper on an in-memory SQLite database holding the single
     * Metadata row (Id = 1) that the helper always reads.
     */
    private function makeMetadataDataHelper(
        bool $isTestSite = false,
        ?string $forcedLanguage = null,
        ?string $prodSiteUrl = null,
    ): MetadataDataHelper {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('
            CREATE TABLE Metadata (
                Id INTEGER PRIMARY KEY,
                ThisIsTestSite INTEGER NOT NULL DEFAULT 0,
                ThisIsForcedLanguage TEXT,
                ThisIsProdSiteUrl TEXT
            )
        ');
        $pdo->prepare(
            'INSERT INTO Metadata (Id, ThisIsTestSite, ThisIsForcedLanguage, ThisIsProdSiteUrl) VALUES (1, ?, ?, ?)'
        )->execute([(int)$isTestSite, $forcedLanguage, $prodSiteUrl]);

        return DataHelperFactory::create(MetadataDataHelper::class, $pdo);
    }

    // -------------------------------------------------------------------------
    // Helpers – ConnectedUser
    // -------------------------------------------------------------------------

    private function makeConnectedUser(
        ?DataHelper $dataHelper = null,
        ?AuthorizationDataHelper $authorizationDataHelper = null,
        ?MetadataDataHelper $metadataDataHelper = null,
        ?GravatarHandlerInterface $gravatarHandler = null,
        ?ErrorManagerInterface $errorManager = null,
    ): ConnectedUser {
        return new ConnectedUser(
            $errorManager            ?? $this->createStub(ErrorManagerInterface::class),
            $dataHelper              ?? $this->makeRealDataHelper(),
            $authorizationDataHelper ?? $this->makeAuthorizationDataHelper(),
            $metadataDataHelper      ?? $this->makeMetadataDataHelper(),
            $gravatarHandler         ?? $this->createStub(GravatarHandlerInterface::class),
        );
    }

    /** @param list<string> $authorizations */
    private function setAuthorizations(ConnectedUser $user, array $authorizations): void
    {
        $person = (new ReflectionClass(Person::class))->newInstanceWithoutConstructor();
        $user->person = $person;
        $user->user   = new ConnectedUserVO($person, $authorizations);
    }

    // -------------------------------------------------------------------------
    // isXxx()
    // -------------------------------------------------------------------------

    public function testIsEditorTrueWhenAuthorizationPresent(): void
    {
        $user = $this->makeConnectedUser();
        $this->setAuthorizations($user, [Authorization::Editor->value]);

        $this->assertTrue($user->isEditor());
        $this->assertFalse($user->isRedactor());
    }

    public function testIsEditorFalseWhenNoAuthorizations(): void
    {
        $user = $this->makeConnectedUser();
        $this->setAuthorizations($user, []);

        $this->assertFalse($user->isEditor());
    }

    public function testIsDesignerTrueForAnyDesignerSubRole(): void
    {
        $cases = [
            Authorization::EventDesigner,
            Authorization::ExerciseDesigner,
            Authorization::HomeDesigner,
            Authorization::KanbanDesigner,
            Authorization::LoanDesigner,
            Authorization::MenuDesigner,
        ];

        foreach ($cases as $authorization) {
            $user = $this->makeConnectedUser();
            $this->setAuthorizations($user, [$authorization->value]);

            $this->assertTrue(
                $user->isDesigner(),
                "isDesigner() should be true for {$authorization->name}"
            );
        }
    }

    public function testIsDesignerFalseWhenUnrelatedAuthorization(): void
    {
        $user = $this->makeConnectedUser();
        $this->setAuthorizations($user, [Authorization::Translator->value]);

        $this->assertFalse($user->isDesigner());
    }

    public function testIsAdministratorTrueForWebmaster(): void
    {
        $user = $this->makeConnectedUser();
        $this->setAuthorizations($user, [Authorization::Webmaster->value]);

        $this->assertTrue($user->isAdministrator());
    }

    public function testIsAdministratorFalseWhenOnlyMemberNoSpecialRole(): void
    {
        $user = $this->makeConnectedUser();
        $this->setAuthorizations($user, []);

        $this->assertFalse($user->isAdministrator());
    }

    public function testIsGroupManagerTrueForPersonManagerOrWebmaster(): void
    {
        $user = $this->makeConnectedUser();
        $this->setAuthorizations($user, [Authorization::PersonManager->value]);
        $this->assertTrue($user->isGroupManager());

        $user2 = $this->makeConnectedUser();
        $this->setAuthorizations($user2, [Authorization::Webmaster->value]);
        $this->assertTrue($user2->isGroupManager());
    }

    public function testIsLoanTrueForDesignerOrManager(): void
    {
        $user = $this->makeConnectedUser();
        $this->setAuthorizations($user, [Authorization::LoanDesigner->value]);
        $this->assertTrue($user->isLoan());
    }

    // -------------------------------------------------------------------------
    // hasAutorization() / hasOnlyOneAutorization()
    // -------------------------------------------------------------------------

    public function testHasAutorizationFalseWhenEmpty(): void
    {
        $user = $this->makeConnectedUser();
        $this->setAuthorizations($user, []);

        $this->assertFalse($user->hasAutorization());
        $this->assertFalse($user->hasOnlyOneAutorization());
    }

    public function testHasOnlyOneAutorizationTrueWithExactlyOne(): void
    {
        $user = $this->makeConnectedUser();
        $this->setAuthorizations($user, [Authorization::Redactor->value]);

        $this->assertTrue($user->hasAutorization());
        $this->assertTrue($user->hasOnlyOneAutorization());
    }

    public function testHasOnlyOneAutorizationFalseWithSeveral(): void
    {
        $user = $this->makeConnectedUser();
        $this->setAuthorizations($user, [
            Authorization::Redactor->value,
            Authorization::Editor->value,
        ]);

        $this->assertTrue($user->hasAutorization());
        $this->assertFalse($user->hasOnlyOneAutorization());
    }

    // -------------------------------------------------------------------------
    // isConnected()
    // -------------------------------------------------------------------------

    public function testIsConnectedFalseByDefault(): void
    {
        $user = $this->makeConnectedUser();

        $this->assertFalse($user->isConnected());
    }

    public function testIsConnectedTrueWhenPersonSet(): void
    {
        $user = $this->makeConnectedUser();

        // Person is final readonly: it cannot be mocked
        $person = (new ReflectionClass(Person::class))->newInstanceWithoutConstructor();
        $user->person = $person;
        $user->user   = new ConnectedUserVO($person, []);

        $this->assertTrue($user->isConnected());
    }

    // -------------------------------------------------------------------------
    // getPage()
    // -------------------------------------------------------------------------

    public function testGetPageReturnsEmptyStringWhenNoUri(): void
    {
        unset($_SERVER['REQUEST_URI']);
        $user = $this->makeConnectedUser();

        $this->assertSame('', $user->getPage());
        $this->assertNull($user->getPage(1));
    }

    public function testGetPageReturnsFirstSegmentByDefault(): void
    {
        $_SERVER['REQUEST_URI'] = '/events/42/edit';
        $user = $this->makeConnectedUser();

        $this->assertSame('events', $user->getPage());
        $this->assertSame('42', $user->getPage(1));
        $this->assertSame('edit', $user->getPage(2));
        $this->assertNull($user->getPage(9));
    }

    public function testGetPageHandlesQueryString(): void
    {
        $_SERVER['REQUEST_URI'] = '/kanban?board=3';
        $user = $this->makeConnectedUser();

        $this->assertSame('kanban', $user->getPage());
    }

    // -------------------------------------------------------------------------
    // getLastSignIn() / getLastSignOut() when not connected
    // -------------------------------------------------------------------------

    public function testGetLastSignInReturnsNullWhenNotConnected(): void
    {
        $user = $this->makeConnectedUser();

        $this->assertNull($user->getLastSignIn());
        $this->assertNull($user->getLastSignOut());
    }

    // -------------------------------------------------------------------------
    // get(): guard branches
    // -------------------------------------------------------------------------

    public function testGetEarlyReturnWhenNoSessionUser(): void
    {
        unset($_SESSION['user']);
        $user = $this->makeConnectedUser();

        $user->get();

        $this->assertFalse($user->isConnected());
        $this->assertFalse($user->hasAutorization());
    }

    public function testGetEarlyReturnAndClearsSessionWhenUnknownEmail(): void
    {
        $_SESSION['user'] = 'unknown@example.com';

        $errorManager = $this->createMock(ErrorManagerInterface::class);
        $errorManager->expects($this->once())
            ->method('raise')
            ->with(
                ApplicationError::BadRequest,
                $this->stringContains('Unknown user with this email address unknown@example.com')
            );

        // Empty database: get() returns false
        $user = $this->makeConnectedUser(
            dataHelper: $this->makeRealDataHelper(),
            errorManager: $errorManager,
        );

        $user->get();

        $this->assertSame('', $_SESSION['user']);
        $this->assertFalse($user->isConnected());
        $this->assertFalse($user->hasAutorization());
    }

    // -------------------------------------------------------------------------
    // get(): success path (partial)
    // -------------------------------------------------------------------------

    public function testGetSuccessPathSetsPersonAndAuthorizations(): void
    {
        $_SESSION['user'] = 'known@example.com';

        $dataHelper = $this->makeRealDataHelper();

        $pdo = (new ReflectionClass($dataHelper))->getProperty('pdo')->getValue($dataHelper);
        assert($pdo instanceof PDO);
        $pdo->exec("
            INSERT INTO Individual (Type, FirstName, LastName, Email, NickName, Avatar)
            VALUES ('Member', 'Jean', 'Dupont', 'known@example.com', NULL, NULL)
        ");
        $id = (int) $pdo->lastInsertId();
        $pdo->exec("
            INSERT INTO Member (Id, Alert, UseGravatar)
            VALUES ($id, NULL, 0)
        ");

        $authPdo = $this->makeAuthorizationSchemaPdo();
        $this->givenAuthorizations($authPdo, $id, [Authorization::Editor->value]);

        $user = $this->makeConnectedUser(
            dataHelper: $dataHelper,
            authorizationDataHelper: $this->makeAuthorizationDataHelper($authPdo),
        );

        $user->get();

        $this->assertTrue($user->isConnected());
        $this->assertTrue($user->isEditor());
        $this->assertFalse($user->isRedactor());
        $this->assertInstanceOf(Person::class, $user->person);
        $this->assertSame('known@example.com', $user->person->Email);
    }

    // -------------------------------------------------------------------------
    // Database schema
    // -------------------------------------------------------------------------

    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
            'NickName',
            'Avatar',
        ]);

        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Alert',
            'UseGravatar',
            'LastSignIn',
            'LastSignOut',
        ]);
    }
}