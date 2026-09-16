<?php

declare(strict_types=1);

namespace tests\helpers;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use PDO;
use app\helpers\ConnectedUser;
use app\helpers\ErrorManager;
use app\helpers\GravatarHandler;
use app\enums\Authorization;
use app\enums\ApplicationError;
use app\modules\Common\valueObjects\ConnectedUser as ConnectedUserVO;
use app\modules\Common\valueObjects\Person;
use app\models\DataHelper;
use app\models\AuthorizationDataHelper;
use app\models\MetadataDataHelper;

/**
 * NOTE: getLastSignIn() / getLastSignOut() success paths and the full
 * success path of get() still touch static helpers (Person::fromRow,
 * Params::*, TranslationManager::*, WebApp::*). Those are left for a
 * later refactor that would make them injectable.
 *
 * Here we test everything that only depends on internal state or on
 * the injectable collaborators.
 */
final class ConnectedUserTest extends TestCase
{
    private array $server;
    private array $session;

    protected function setUp(): void
    {
        $this->server  = $_SERVER;
        $this->session = $_SESSION ?? [];
    }

    protected function tearDown(): void
    {
        $_SERVER  = $this->server;
        $_SESSION = $this->session;
    }

    /**
     * Crée un DataHelper réel et très léger (SQLite en mémoire).
     * Parfait pour les tests qui n’ont pas besoin de contrôler
     * finement le comportement de get()/set()/…
     */
    private function makeRealDataHelper(): DataHelper
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Schéma minimal nécessaire pour les tests qui touchent vraiment la DB
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
            $this->createStub(ErrorManager::class)
        );
    }

    /**
     * Construit un ConnectedUser.
     * DataHelper est toujours un vrai objet (car final).
     * Les autres collaborateurs restent mockables tant qu’ils ne sont pas final.
     */
    private function makeConnectedUser(
        ?DataHelper $dataHelper = null,
        ?AuthorizationDataHelper $authorizationDataHelper = null,
        ?MetadataDataHelper $metadataDataHelper = null,
        ?GravatarHandler $gravatarHandler = null,
        ?ErrorManager $errorManager = null,
    ): ConnectedUser {
        return new ConnectedUser(
            $errorManager            ?? $this->createStub(ErrorManager::class),
            $dataHelper              ?? $this->makeRealDataHelper(),
            $authorizationDataHelper ?? $this->createStub(AuthorizationDataHelper::class),
            $metadataDataHelper      ?? $this->createStub(MetadataDataHelper::class),
            $gravatarHandler         ?? $this->createStub(GravatarHandler::class),
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
    // isXxx() unit tests
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

        // Person is final readonly → cannot be mocked.
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
    // get() : isolable guard branches
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

        $errorManager = $this->createMock(ErrorManager::class);
        $errorManager->expects($this->once())
            ->method('raise')
            ->with(
                ApplicationError::BadRequest,
                $this->stringContains('Unknown user with this email address unknown@example.com')
            );

        // DataHelper réel + base vide → get() retourne false
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
    // get() success path (partial)
    // -------------------------------------------------------------------------

    public function testGetSuccessPathSetsPersonAndAuthorizations(): void
    {
        $_SESSION['user'] = 'known@example.com';

        $dataHelper = $this->makeRealDataHelper();

        // On insère les données nécessaires
        $pdo = (new ReflectionClass($dataHelper))->getProperty('pdo')->getValue($dataHelper);
        $pdo->exec("
            INSERT INTO Individual (Type, FirstName, LastName, Email, NickName, Avatar)
            VALUES ('Member', 'Jean', 'Dupont', 'known@example.com', NULL, NULL)
        ");
        $id = (int) $pdo->lastInsertId();
        $pdo->exec("
            INSERT INTO Member (Id, Alert, UseGravatar)
            VALUES ($id, NULL, 0)
        ");

        $authorizationDataHelper = $this->createMock(AuthorizationDataHelper::class);
        $authorizationDataHelper->expects($this->once())
            ->method('getsFor')
            ->willReturn([Authorization::Editor->value]);

        $metadataDataHelper = $this->createStub(MetadataDataHelper::class);
        $metadataDataHelper->method('isTestSite')->willReturn(false);

        $user = $this->makeConnectedUser(
            dataHelper: $dataHelper,
            authorizationDataHelper: $authorizationDataHelper,
            metadataDataHelper: $metadataDataHelper,
        );

        $user->get();

        $this->assertTrue($user->isConnected());
        $this->assertTrue($user->isEditor());
        $this->assertFalse($user->isRedactor());
        $this->assertSame('known@example.com', $user->person->Email);
    }
}
