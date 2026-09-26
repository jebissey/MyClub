<?php

declare(strict_types=1);

namespace tests\modules\Common\services;

use PHPUnit\Framework\TestCase;
use app\exceptions\EmailException;
use app\helpers\Password;
use app\models\interfaces\MemberDataHelperInterface;
use app\modules\Common\services\AuthenticationService;

final class AuthenticationServiceTest extends TestCase
{
    private array $session;
    private array $cookie;

    protected function setUp(): void
    {
        $this->session = $_SESSION ?? [];
        $this->cookie  = $_COOKIE ?? [];
        $_SESSION = [];
        $_COOKIE  = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->session;
        $_COOKIE  = $this->cookie;
    }

    private function makeService(?MemberDataHelperInterface $memberDataHelper = null): AuthenticationService
    {
        return new AuthenticationService(
            $memberDataHelper ?? $this->createStub(MemberDataHelperInterface::class),
            'https://myclub.test'
        );
    }

    // -------------------------------------------------------------------------
    // handleSignIn
    // -------------------------------------------------------------------------

    public function testHandleSignInRejectsInvalidEmail(): void
    {
        $result = $this->makeService()->handleSignIn([
            'email'    => 'not-an-email',
            'password' => 'validpassword',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Invalid email address', $result->getError());
    }

    public function testHandleSignInRejectsInvalidPassword(): void
    {
        $result = $this->makeService()->handleSignIn([
            'email'    => 'user@example.com',
            'password' => '123',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Password rules are not respected', $result->getError());
    }

    public function testHandleSignInSuccess(): void
    {
        $row = (object) [
            'Id'          => 42,
            'Email'       => 'user@example.com',
            'FirstName'   => 'Jean',
            'LastName'    => 'Dupont',
            'NickName'    => null,
            'Avatar'      => null,
            'Password'    => Password::signPassword('validpassword'),
            'Inactivated' => 0,
            'UseGravatar' => 'no',
            'Alert'       => null,
        ];

        $memberDataHelper = $this->createMock(MemberDataHelperInterface::class);
        $memberDataHelper->expects($this->once())
            ->method('findForSignIn')
            ->with('user@example.com')
            ->willReturn($row);
        $memberDataHelper->expects($this->once())
            ->method('recordSignIn')
            ->with(42);

        $result = $this->makeService($memberDataHelper)->handleSignIn([
            'email'      => 'user@example.com',
            'password'   => 'validpassword',
            'rememberMe' => '',
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('user@example.com', $result->getUser()?->Email);
        $this->assertSame('user@example.com', $_SESSION['user']);
    }

    public function testHandleSignInFailsWhenUserInactivated(): void
    {
        $row = (object) [
            'Id' => 42,
            'Email' => 'user@example.com',
            'FirstName' => 'Jean',
            'LastName' => 'Dupont',
            'NickName' => null,
            'Avatar' => null,
            'Password' => Password::signPassword('validpassword'),
            'Inactivated' => 1,
            'UseGravatar' => 'no',
            'Alert' => null,
        ];

        $memberDataHelper = $this->createStub(MemberDataHelperInterface::class);
        $memberDataHelper->method('findForSignIn')->willReturn($row);

        $result = $this->makeService($memberDataHelper)->handleSignIn([
            'email'    => 'user@example.com',
            'password' => 'validpassword',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('inactivated user', $result->getError());
    }

    public function testHandleSignInFailsWithWrongPassword(): void
    {
        $row = (object) [
            'Id' => 42,
            'Email' => 'user@example.com',
            'FirstName' => 'Jean',
            'LastName' => 'Dupont',
            'NickName' => null,
            'Avatar' => null,
            'Password' => Password::signPassword('correctpassword'),
            'Inactivated' => 0,
            'UseGravatar' => 'no',
            'Alert' => null,
        ];

        $memberDataHelper = $this->createStub(MemberDataHelperInterface::class);
        $memberDataHelper->method('findForSignIn')->willReturn($row);

        $result = $this->makeService($memberDataHelper)->handleSignIn([
            'email'    => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('wrong password', $result->getError());
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

        $row = (object) [
            'Id' => 42,
            'Email' => 'user@example.com',
            'FirstName' => 'Jean',
            'LastName' => 'Dupont',
            'NickName' => null,
            'Avatar' => null,
            'Inactivated' => 0,
        ];

        $memberDataHelper = $this->createMock(MemberDataHelperInterface::class);
        $memberDataHelper->expects($this->once())
            ->method('findByRememberToken')
            ->with('valid-token')
            ->willReturn($row);
        $memberDataHelper->expects($this->once())->method('recordSignIn')->with(42);

        $result = $this->makeService($memberDataHelper)->handleRememberMeLogin();

        $this->assertNotNull($result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame('user@example.com', $_SESSION['user']);
    }

    public function testHandleRememberMeLoginClearsCookieWhenTokenInvalid(): void
    {
        $_COOKIE['rememberMe'] = 'invalid-token';

        $memberDataHelper = $this->createStub(MemberDataHelperInterface::class);
        $memberDataHelper->method('findByRememberToken')->willReturn(false);

        $this->assertNull($this->makeService($memberDataHelper)->handleRememberMeLogin());
    }

    // -------------------------------------------------------------------------
    // signOut
    // -------------------------------------------------------------------------

    public function testSignOutClearsSessionAndUpdatesLastSignOut(): void
    {
        $_SESSION['user'] = 'user@example.com';

        $memberDataHelper = $this->createMock(MemberDataHelperInterface::class);
        $memberDataHelper->expects($this->once())
            ->method('recordSignOutByEmail')
            ->with('user@example.com')
            ->willReturn(true);

        $this->makeService($memberDataHelper)->signOut();

        $this->assertArrayNotHasKey('user', $_SESSION);
        $this->assertSame('', $_SESSION['navbar']);
    }

    // -------------------------------------------------------------------------
    // resetPassword
    // -------------------------------------------------------------------------

    public function testResetPasswordFailsWhenTokenUnknown(): void
    {
        $memberDataHelper = $this->createStub(MemberDataHelperInterface::class);
        $memberDataHelper->method('findByResetToken')->willReturn(false);

        $this->assertFalse($this->makeService($memberDataHelper)->resetPassword('unknown-token', 'newpassword'));
    }

    public function testResetPasswordFailsWhenTokenExpired(): void
    {
        $member = (object) [
            'Id'             => 42,
            'TokenCreatedAt' => (new \DateTime('-2 hours'))->format('Y-m-d H:i:s'),
        ];

        $memberDataHelper = $this->createStub(MemberDataHelperInterface::class);
        $memberDataHelper->method('findByResetToken')->willReturn($member);

        $this->assertFalse($this->makeService($memberDataHelper)->resetPassword('expired-token', 'newpassword'));
    }

    public function testResetPasswordSuccess(): void
    {
        $member = (object) [
            'Id'             => 42,
            'TokenCreatedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $memberDataHelper = $this->createMock(MemberDataHelperInterface::class);
        $memberDataHelper->method('findByResetToken')->willReturn($member);
        $memberDataHelper->expects($this->once())
            ->method('finalizeReset')
            ->with(42, $this->callback(fn($password) => is_string($password) && $password !== 'newpassword123'));

        $this->assertTrue($this->makeService($memberDataHelper)->resetPassword('valid-token', 'newpassword123'));
    }

    // -------------------------------------------------------------------------
    // prepareForgotPasswordEmail
    // -------------------------------------------------------------------------

    public function testPrepareForgotPasswordEmailThrowsWhenEmailUnknown(): void
    {
        $memberDataHelper = $this->createStub(MemberDataHelperInterface::class);
        $memberDataHelper->method('findBasicByEmail')->willReturn(false);

        $this->expectException(EmailException::class);
        $this->makeService($memberDataHelper)->prepareForgotPasswordEmail('unknown@example.com');
    }

    public function testPrepareForgotPasswordEmailStoresTokenAndBuildsMessage(): void
    {
        $row = (object) [
            'Id' => 42,
            'Email' => 'user@example.com',
            'FirstName' => 'Jean',
            'LastName' => 'Dupont',
            'NickName' => null,
            'Avatar' => null,
            'UseGravatar' => 'no',
            'Alert' => null,
        ];

        $memberDataHelper = $this->createMock(MemberDataHelperInterface::class);
        $memberDataHelper->method('findBasicByEmail')->willReturn($row);
        $memberDataHelper->expects($this->once())
            ->method('setResetToken')
            ->with(42, $this->callback(fn(string $token) => strlen($token) === 64));

        $message = $this->makeService($memberDataHelper)->prepareForgotPasswordEmail('user@example.com');

        $this->assertSame('user@example.com', $message->to);
        $this->assertStringContainsString('/user/setPassword/', $message->body);
    }
}
