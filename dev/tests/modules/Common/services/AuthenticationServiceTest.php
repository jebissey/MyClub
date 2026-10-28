<?php

declare(strict_types=1);

namespace tests\modules\Common\services;

use DateTime;
use PDO;
use PHPUnit\Framework\TestCase;
use app\exceptions\EmailException;
use app\helpers\Password;
use app\models\MemberDataHelper;
use app\modules\Common\services\AuthenticationService;
use tests\support\DataHelperFactory;

final class AuthenticationServiceTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $session;

    /**
     * @var array<string, mixed>
     */
    private array $cookie;

    private PDO $pdo;

    protected function setUp(): void
    {
        $this->session = $_SESSION;
        $this->cookie = $_COOKIE;

        $_SESSION = [];
        $_COOKIE = [];

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('
            CREATE TABLE Individual (
                Id INTEGER PRIMARY KEY,
                Email TEXT,
                FirstName TEXT,
                LastName TEXT,
                NickName TEXT,
                Avatar TEXT
            );
            CREATE TABLE Member (
                Id INTEGER PRIMARY KEY,
                Password TEXT,
                Inactivated INTEGER NOT NULL DEFAULT 0,
                UseGravatar INTEGER NOT NULL DEFAULT 0,
                Alert TEXT,
                Token TEXT,
                TokenCreatedAt TEXT,
                LastSignIn TEXT,
                LastSignOut TEXT
            );
        ');
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->session;
        $_COOKIE = $this->cookie;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * MemberDataHelper is final and needs a full Application: the real class is used,
     * on top of an in-memory SQLite database.
     */
    private function makeService(): AuthenticationService
    {
        return new AuthenticationService(
            DataHelperFactory::create(MemberDataHelper::class, $this->pdo),
            'https://myclub.test'
        );
    }

    private function givenMember(
        int $id = 42,
        string $email = 'user@example.com',
        string $password = 'validpassword',
        bool $inactivated = false,
        ?string $token = null,
        ?string $tokenCreatedAt = null,
    ): void {
        $this->pdo->prepare(
            'INSERT INTO Individual (Id, Email, FirstName, LastName) VALUES (?, ?, ?, ?)'
        )->execute([$id, $email, 'Jean', 'Dupont']);

        $this->pdo->prepare(
            'INSERT INTO Member (Id, Password, Inactivated, Token, TokenCreatedAt) VALUES (?, ?, ?, ?, ?)'
        )->execute([$id, Password::signPassword($password), (int)$inactivated, $token, $tokenCreatedAt]);
    }

    /**
     * An Individual that has no Member row.
     */
    private function givenIndividualWithoutMember(int $id, string $email): void
    {
        $this->pdo->prepare(
            'INSERT INTO Individual (Id, Email, FirstName, LastName) VALUES (?, ?, ?, ?)'
        )->execute([$id, $email, 'Jean', 'Dupont']);
    }

    private function memberColumn(int $id, string $column): mixed
    {
        $statement = $this->pdo->prepare("SELECT {$column} FROM Member WHERE Id = ?");
        $statement->execute([$id]);

        return $statement->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // handleSignIn
    // -------------------------------------------------------------------------

    public function testHandleSignInRejectsInvalidEmail(): void
    {
        $result = $this->makeService()->handleSignIn([
            'email' => 'not-an-email',
            'password' => 'validpassword',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString(
            'Invalid email address',
            $result->getError()
        );
    }

    public function testHandleSignInRejectsInvalidPassword(): void
    {
        $result = $this->makeService()->handleSignIn([
            'email' => 'user@example.com',
            'password' => '123',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString(
            'Password rules are not respected',
            $result->getError()
        );
    }

    public function testHandleSignInSuccess(): void
    {
        $this->givenMember(id: 42);

        $result = $this->makeService()->handleSignIn([
            'email' => 'user@example.com',
            'password' => 'validpassword',
            'rememberMe' => '',
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('user@example.com', $result->getUser()?->email);
        $this->assertSame('user@example.com', $_SESSION['user']);
        $this->assertNotNull($this->memberColumn(42, 'LastSignIn'));
    }

    public function testHandleSignInFailsWhenUserInactivated(): void
    {
        $this->givenMember(id: 42, inactivated: true);

        $result = $this->makeService()->handleSignIn([
            'email' => 'user@example.com',
            'password' => 'validpassword',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString(
            'inactivated user',
            $result->getError()
        );
        $this->assertNull($this->memberColumn(42, 'LastSignIn'));
    }

    public function testHandleSignInFailsWithWrongPassword(): void
    {
        $this->givenMember(id: 42, password: 'correctpassword');

        $result = $this->makeService()->handleSignIn([
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString(
            'wrong password',
            $result->getError()
        );
        $this->assertNull($this->memberColumn(42, 'LastSignIn'));
    }

    // -------------------------------------------------------------------------
    // handleRememberMeLogin
    // -------------------------------------------------------------------------

    public function testHandleRememberMeLoginReturnsNullWhenNoCookie(): void
    {
        $this->assertNull($this->makeService()->handleRememberMeLogin());
    }

    public function testHandleRememberMeLoginSuccess(): void
    {
        $_COOKIE['rememberMe'] = 'valid-token';
        $this->givenMember(id: 42, token: 'valid-token');

        $result = $this->makeService()->handleRememberMeLogin();

        $this->assertNotNull($result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame('user@example.com', $_SESSION['user']);
        $this->assertNotNull($this->memberColumn(42, 'LastSignIn'));
    }

    public function testHandleRememberMeLoginClearsCookieWhenTokenInvalid(): void
    {
        $_COOKIE['rememberMe'] = 'invalid-token';
        $this->givenMember(id: 42, token: 'another-token');

        $this->assertNull($this->makeService()->handleRememberMeLogin());
        $this->assertNull($this->memberColumn(42, 'LastSignIn'));
    }

    public function testHandleRememberMeLoginReturnsNullWhenMemberInactivated(): void
    {
        $_COOKIE['rememberMe'] = 'valid-token';
        $this->givenMember(id: 42, inactivated: true, token: 'valid-token');

        $this->assertNull($this->makeService()->handleRememberMeLogin());
        $this->assertArrayNotHasKey('user', $_SESSION);
    }

    // -------------------------------------------------------------------------
    // signOut
    // -------------------------------------------------------------------------

    public function testSignOutClearsSessionAndUpdatesLastSignOut(): void
    {
        $_SESSION['user'] = 'user@example.com';
        $this->givenMember(id: 42);

        $this->makeService()->signOut();

        $this->assertArrayNotHasKey('user', $_SESSION);
        $this->assertSame('', $_SESSION['navbar']);
        $this->assertNotNull($this->memberColumn(42, 'LastSignOut'));
    }

    // -------------------------------------------------------------------------
    // resetPassword
    // -------------------------------------------------------------------------

    public function testResetPasswordFailsWhenTokenUnknown(): void
    {
        $this->assertFalse(
            $this->makeService()->resetPassword('unknown-token', 'newpassword')
        );
    }

    public function testResetPasswordFailsWhenTokenExpired(): void
    {
        $this->givenMember(
            id: 42,
            token: 'expired-token',
            tokenCreatedAt: (new DateTime('-2 hours'))->format('Y-m-d H:i:s'),
        );
        $passwordBefore = $this->memberColumn(42, 'Password');

        $this->assertFalse(
            $this->makeService()->resetPassword('expired-token', 'newpassword')
        );
        $this->assertSame($passwordBefore, $this->memberColumn(42, 'Password'));
    }

    public function testResetPasswordSuccess(): void
    {
        $this->givenMember(
            id: 42,
            token: 'valid-token',
            tokenCreatedAt: (new DateTime())->format('Y-m-d H:i:s'),
        );
        $passwordBefore = $this->memberColumn(42, 'Password');

        $this->assertTrue(
            $this->makeService()->resetPassword('valid-token', 'newpassword123')
        );

        $passwordAfter = $this->memberColumn(42, 'Password');
        $this->assertIsString($passwordAfter);
        $this->assertNotSame($passwordBefore, $passwordAfter);
        $this->assertNotSame('newpassword123', $passwordAfter);
        $this->assertNull($this->memberColumn(42, 'Token'));
        $this->assertNull($this->memberColumn(42, 'TokenCreatedAt'));
    }

    // -------------------------------------------------------------------------
    // prepareForgotPasswordEmail
    // -------------------------------------------------------------------------

    public function testPrepareForgotPasswordEmailThrowsWhenEmailUnknown(): void
    {
        $this->expectException(EmailException::class);

        $this->makeService()->prepareForgotPasswordEmail('unknown@example.com');
    }

    public function testPrepareForgotPasswordEmailThrowsWhenIndividualIsNotAMember(): void
    {
        $this->givenIndividualWithoutMember(7, 'contact@example.com');

        $this->expectException(EmailException::class);

        $this->makeService()->prepareForgotPasswordEmail('contact@example.com');
    }

    public function testPrepareForgotPasswordEmailStoresTokenAndBuildsMessage(): void
    {
        $this->givenMember(id: 42);

        $message = $this->makeService()->prepareForgotPasswordEmail('user@example.com');

        $this->assertSame('user@example.com', $message->to);
        $this->assertStringContainsString(
            '/user/setPassword/',
            $message->body
        );

        $token = $this->memberColumn(42, 'Token');
        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
        $this->assertNotNull($this->memberColumn(42, 'TokenCreatedAt'));
    }
}