<?php

declare(strict_types=1);

namespace tests\helpers;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\GravatarHandler;
use app\enums\Authorization;
use app\enums\ApplicationError;
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
     * Builds a ConnectedUser without ever touching a real database.
     * All four collaborators are mocked or stubbed so the constructor never
     * executes "new DataHelper(...)" etc.
     */
    private function makeConnectedUser(
        ?DataHelper $dataHelper = null,
        ?AuthorizationDataHelper $authorizationDataHelper = null,
        ?MetadataDataHelper $metadataDataHelper = null,
        ?GravatarHandler $gravatarHandler = null,
        ?Application $application = null,
    ): ConnectedUser {
        return new ConnectedUser(
            $application              ?? $this->createStub(Application::class),
            $dataHelper              ?? $this->createStub(DataHelper::class),
            $authorizationDataHelper ?? $this->createStub(AuthorizationDataHelper::class),
            $metadataDataHelper      ?? $this->createStub(MetadataDataHelper::class),
            $gravatarHandler         ?? $this->createStub(GravatarHandler::class),
        );
    }

    /** @param array<int, string> $authorizations */
    private function setAuthorizations(ConnectedUser $user, array $authorizations): void
    {
        $ref  = new ReflectionClass($user);
        $prop = $ref->getProperty('authorizations');
        $prop->setValue($user, $authorizations);
    }

    // --- isXxx() unit tests ---

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

    // --- hasAutorization() / hasOnlyOneAutorization() ---

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

    // --- isConnected() ---

    public function testIsConnectedFalseByDefault(): void
    {
        $user = $this->makeConnectedUser();

        $this->assertFalse($user->isConnected());
    }

    public function testIsConnectedTrueWhenPersonSet(): void
    {
        $user = $this->makeConnectedUser();

        // Person is final readonly → cannot be mocked.
        // newInstanceWithoutConstructor is safe here because isConnected()
        // only checks "!== null".
        $person = (new ReflectionClass(Person::class))->newInstanceWithoutConstructor();
        $user->person = $person;

        $this->assertTrue($user->isConnected());
    }

    // --- getPage() ---

    public function testGetPageReturnsEmptyStringWhenNoUri(): void
    {
        unset($_SERVER['REQUEST_URI']);
        $user = $this->makeConnectedUser();

        $this->assertSame('', $user->getPage());
        $this->assertNull($user->getPage(1)); // segment out of bounds → null
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

    // --- getLastSignIn() / getLastSignOut() when not connected ---

    public function testGetLastSignInReturnsNullWhenNotConnected(): void
    {
        $user = $this->makeConnectedUser();

        $this->assertNull($user->getLastSignIn());
        $this->assertNull($user->getLastSignOut());
    }

    // --- get() : isolable guard branches ---

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

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('get')
            ->with(
                'Person',
                ['Email' => 'unknown@example.com'],
                'Id, Email, Alert, FirstName, LastName, NickName, UseGravatar, Avatar'
            )
            ->willReturn(false);

        $errorManager = $this->createMock(\app\helpers\ErrorManager::class); // adjust namespace if needed
        $errorManager->expects($this->once())
            ->method('raise')
            ->with(
                ApplicationError::BadRequest,
                $this->stringContains('Unknown user with this email address unknown@example.com')
            );

        $application = $this->createStub(Application::class);
        $application->method('getErrorManager')->willReturn($errorManager);

        $user = $this->makeConnectedUser(
            dataHelper: $dataHelper,
            application: $application,
        );

        $user->get();

        $this->assertSame('', $_SESSION['user']);
        $this->assertFalse($user->isConnected());
        $this->assertFalse($user->hasAutorization());
    }

    // --- get() success path (partial) ---
    // Static helpers (Person::fromRow, Params::*, TranslationManager::*,
    // WebApp::*) still run. Only the injectable collaborators are mocked.

    public function testGetSuccessPathSetsPersonAndAuthorizations(): void
    {
        $_SESSION['user'] = 'known@example.com';

        $personRow = (object) [
            'Id'          => 42,
            'Email'       => 'known@example.com',
            'Alert'       => null,
            'FirstName'   => 'Jean',
            'LastName'    => 'Dupont',
            'NickName'    => null,
            'UseGravatar' => false,
            'Avatar'      => null,
        ];

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('get')
            ->with(
                'Person',
                ['Email' => 'known@example.com'],
                'Id, Email, Alert, FirstName, LastName, NickName, UseGravatar, Avatar'
            )
            ->willReturn($personRow);

        $dataHelper->method('getDefaultColors')
            ->willReturn([
                'navbarBgColor'   => '#000',
                'navbarInkColor'  => '#fff',
                'navbarIconColor' => '#ccc',
            ]);

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
