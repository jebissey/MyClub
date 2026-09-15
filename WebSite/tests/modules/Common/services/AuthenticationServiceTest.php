<?php

declare(strict_types=1);

namespace tests\modules\Common\services;

use PHPUnit\Framework\TestCase;
use app\exceptions\EmailException;
use app\helpers\Password;
use app\models\DataHelper;
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

    private function makeService(?DataHelper $dataHelper = null): AuthenticationService
    {
        return new AuthenticationService($dataHelper ?? $this->createStub(DataHelper::class), 'https://myclub.test');
    }

    // -------------------------------------------------------------------------
    // handleSignIn
    // -------------------------------------------------------------------------

    public function testHandleSignInRejectsInvalidEmail(): void
    {
        $service = $this->makeService();

        $result = $service->handleSignIn([
            'email'    => 'not-an-email',
            'password' => 'validpassword',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Invalid email address', $result->getError());
    }

    public function testHandleSignInRejectsInvalidPassword(): void
    {
        $service = $this->makeService();

        $result = $service->handleSignIn([
            'email'    => 'user@example.com',
            'password' => '123', // trop court
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Password rules are not respected', $result->getError());
    }

    public function testHandleSignInSuccess(): void
    {
        $individual = (object) [
            'Id'        => 42,
            'Email'     => 'user@example.com',
            'FirstName' => 'Jean',
            'LastName'  => 'Dupont',
            'NickName'  => null,
            'Avatar'    => null,
        ];

        $member = (object) [
            'Password'     => Password::signPassword('validpassword'),
            'Inactivated'  => 0,
            'UseGravatar'  => 'no',
            'Alert'        => null,
        ];

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $table, array $where) use ($individual, $member) {
                if ($table === 'Individual') {
                    $this->assertSame(['Email' => 'user@example.com'], $where);
                    return $individual;
                }
                if ($table === 'Member') {
                    $this->assertSame(['Id' => 42], $where);
                    return $member;
                }
                $this->fail("Unexpected table $table");
            });

        $dataHelper->expects($this->once())
            ->method('set')
            ->with('Member', $this->arrayHasKey('LastSignIn'), ['Id' => 42]);

        $service = $this->makeService($dataHelper);

        $result = $service->handleSignIn([
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
        $individual = (object) [
            'Id'        => 42,
            'Email'     => 'user@example.com',
            'FirstName' => 'Jean',
            'LastName'  => 'Dupont',
            'NickName'  => null,
            'Avatar'    => null,
        ];

        $member = (object) [
            'Password'     => Password::signPassword('validpassword'),
            'Inactivated'  => 1,
            'UseGravatar'  => 'no',
            'Alert'        => null,
        ];

        $dataHelper = $this->createStub(DataHelper::class);
        $dataHelper->method('get')->willReturnCallback(
            fn(string $table) => $table === 'Individual' ? $individual : $member
        );

        $service = $this->makeService($dataHelper);

        $result = $service->handleSignIn([
            'email'    => 'user@example.com',
            'password' => 'validpassword',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('inactivated user', $result->getError());
    }

    public function testHandleSignInFailsWithWrongPassword(): void
    {
        $individual = (object) [
            'Id'        => 42,
            'Email'     => 'user@example.com',
            'FirstName' => 'Jean',
            'LastName'  => 'Dupont',
            'NickName'  => null,
            'Avatar'    => null,
        ];

        $member = (object) [
            'Password'     => Password::signPassword('correctpassword'),
            'Inactivated'  => 0,
            'UseGravatar'  => 'no',
            'Alert'        => null,
        ];

        $dataHelper = $this->createStub(DataHelper::class);
        $dataHelper->method('get')->willReturnCallback(
            fn(string $table) => $table === 'Individual' ? $individual : $member
        );

        $service = $this->makeService($dataHelper);

        $result = $service->handleSignIn([
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
        $service = $this->makeService();
        $this->assertNull($service->handleRememberMeLogin());
    }

    public function testHandleRememberMeLoginSuccess(): void
    {
        $_COOKIE['rememberMe'] = 'valid-token';

        $member = (object) [
            'Id'          => 42,
            'Inactivated' => 0,
        ];

        $individual = (object) [
            'Id'        => 42,
            'Email'     => 'user@example.com',
            'FirstName' => 'Jean',
            'LastName'  => 'Dupont',
            'NickName'  => null,
            'Avatar'    => null,
        ];

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturnCallback(
            function (string $table) use ($member, $individual) {
                return $table === 'Member' ? $member : $individual;
            }
        );
        $dataHelper->expects($this->once())->method('set');

        $service = $this->makeService($dataHelper);
        $result  = $service->handleRememberMeLogin();

        $this->assertNotNull($result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame('user@example.com', $_SESSION['user']);
    }

    public function testHandleRememberMeLoginClearsCookieWhenTokenInvalid(): void
    {
        $_COOKIE['rememberMe'] = 'invalid-token';

        $dataHelper = $this->createStub(DataHelper::class);
        $dataHelper->method('get')->willReturn(false);

        $service = $this->makeService($dataHelper);
        $result  = $service->handleRememberMeLogin();

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // signOut
    // -------------------------------------------------------------------------

    public function testSignOutClearsSessionAndUpdatesLastSignOut(): void
    {
        $_SESSION['user'] = 'user@example.com';

        $individual = (object) ['Id' => 42];

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->expects($this->once())
            ->method('get')
            ->with('Individual', ['Email' => 'user@example.com'], 'Id')
            ->willReturn($individual);

        $dataHelper->expects($this->once())
            ->method('set')
            ->with(
                'Member',
                $this->arrayHasKey('LastSignOut'),
                ['Id' => 42]
            );

        $service = $this->makeService($dataHelper);
        $service->signOut();

        $this->assertArrayNotHasKey('user', $_SESSION);
        $this->assertSame('', $_SESSION['navbar']);
    }

    // -------------------------------------------------------------------------
    // resetPassword
    // -------------------------------------------------------------------------

    public function testResetPasswordFailsWhenTokenUnknown(): void
    {
        $dataHelper = $this->createStub(DataHelper::class);
        $dataHelper->method('get')->willReturn(false);

        $service = $this->makeService($dataHelper);
        $this->assertFalse($service->resetPassword('unknown-token', 'newpassword'));
    }

    public function testResetPasswordFailsWhenTokenExpired(): void
    {
        $member = (object) [
            'Id'             => 42,
            'TokenCreatedAt' => (new \DateTime('-2 hours'))->format('Y-m-d H:i:s'),
        ];

        $dataHelper = $this->createStub(DataHelper::class);
        $dataHelper->method('get')->willReturn($member);

        $service = $this->makeService($dataHelper);
        $this->assertFalse($service->resetPassword('expired-token', 'newpassword'));
    }

    public function testResetPasswordSuccess(): void
    {
        $member = (object) [
            'Id'             => 42,
            'TokenCreatedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturn($member);
        $dataHelper->expects($this->once())
            ->method('set')
            ->with(
                'Member',
                $this->callback(function (array $data) {
                    return isset($data['Password'])
                        && $data['Token'] === null
                        && $data['TokenCreatedAt'] === null;
                }),
                ['Id' => 42]
            );

        $service = $this->makeService($dataHelper);
        $this->assertTrue($service->resetPassword('valid-token', 'newpassword123'));
    }

    // -------------------------------------------------------------------------
    // prepareForgotPasswordEmail
    // -------------------------------------------------------------------------

    public function testPrepareForgotPasswordEmailThrowsWhenEmailUnknown(): void
    {
        $dataHelper = $this->createStub(DataHelper::class);
        $dataHelper->method('get')->willReturn(false);

        $service = $this->makeService($dataHelper);

        $this->expectException(EmailException::class);
        $service->prepareForgotPasswordEmail('unknown@example.com');
    }

    public function testPrepareForgotPasswordEmailStoresTokenAndBuildsMessage(): void
    {
        $individual = (object) [
            'Id'        => 42,
            'Email'     => 'user@example.com',
            'FirstName' => 'Jean',
            'LastName'  => 'Dupont',
            'NickName'  => null,
            'Avatar'    => null,
        ];

        $member = (object) [
            'UseGravatar' => 'no',
            'Alert'       => null,
        ];

        $dataHelper = $this->createMock(DataHelper::class);
        $dataHelper->method('get')->willReturnCallback(
            fn(string $table) => $table === 'Individual' ? $individual : $member
        );
        $dataHelper->expects($this->once())
            ->method('set')
            ->with(
                'Member',
                $this->callback(
                    fn(array $data) => isset($data['Token'], $data['TokenCreatedAt'])
                        && strlen($data['Token']) === 64
                ),
                ['Id' => 42]
            );

        $service = $this->makeService($dataHelper);
        $message = $service->prepareForgotPasswordEmail('user@example.com');

        $this->assertSame('user@example.com', $message->to);
        $this->assertStringContainsString('/user/setPassword/', $message->body);
    }
}