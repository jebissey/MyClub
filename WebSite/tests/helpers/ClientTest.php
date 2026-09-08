<?php

declare(strict_types=1);

namespace Tests\helpers;

use PHPUnit\Framework\TestCase;
use app\helpers\Client;

final class ClientTest extends TestCase
{
    private array $server;
    private array $cookie;
    private array $session;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $this->cookie = $_COOKIE;
        $this->session = $_SESSION ?? [];

        unset(
            $_SERVER['HTTP_USER_AGENT'],
            $_SERVER['HTTP_CLIENT_IP'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_REFERER'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD']
        );
        $_COOKIE = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_COOKIE = $this->cookie;
        $_SESSION = $this->session;
    }

    // --- constructor / user-agent parsing ---

    public function testDefaultsWhenNoUserAgent(): void
    {
        $client = new Client();

        $this->assertSame('Unknown ', $client->getBrowser());
        $this->assertSame('Unknown', $client->getOS());
        $this->assertSame('Unknown', $client->getType());
    }

    public function testDefaultsWhenUserAgentIsEmptyString(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = '';

        $client = new Client();

        $this->assertSame('Unknown ', $client->getBrowser());
        $this->assertSame('Unknown', $client->getOS());
    }

    public function testParsesKnownChromeWindowsUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] =
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36';

        $client = new Client();

        $this->assertStringContainsString('Chrome', $client->getBrowser());
        $this->assertSame('Windows', $client->getOS());
    }

    public function testParsesKnownIphoneUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] =
            'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) '
            . 'AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1';

        $client = new Client();

        $this->assertSame('iOS', $client->getOS());
        $this->assertStringContainsString('iPhone', $client->getType());
    }

    public function testGarbageUserAgentDoesNotThrow(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'not-a-real-user-agent-string';

        $client = new Client();

        $this->assertIsString($client->getBrowser());
        $this->assertIsString($client->getOS());
        $this->assertIsString($client->getType());
    }

    // --- getIp() ---

    public function testGetIpReturnsDefaultWhenNothingSet(): void
    {
        $client = new Client();

        $this->assertSame('0.0.0.0', $client->getIp());
    }

    public function testGetIpUsesRemoteAddrWhenOnlyThatIsSet(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';

        $client = new Client();

        $this->assertSame('203.0.113.5', $client->getIp());
    }

    public function testGetIpPrefersXForwardedForOverRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.7';

        $client = new Client();

        $this->assertSame('198.51.100.7', $client->getIp());
    }

    public function testGetIpPrefersClientIpOverAllOthers(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.7';
        $_SERVER['HTTP_CLIENT_IP'] = '192.0.2.9';

        $client = new Client();

        $this->assertSame('192.0.2.9', $client->getIp());
    }

    // --- getReferer() ---

    public function testGetRefererReturnsEmptyStringWhenNotSet(): void
    {
        $client = new Client();

        $this->assertSame('', $client->getReferer());
    }

    public function testGetRefererReturnsValueWhenSet(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/page';

        $client = new Client();

        $this->assertSame('https://example.com/page', $client->getReferer());
    }

    // --- getScreenResolution() ---

    public function testGetScreenResolutionReturnsEmptyStringWhenNotSet(): void
    {
        $client = new Client();

        $this->assertSame('', $client->getScreenResolution());
    }

    public function testGetScreenResolutionReturnsCookieValue(): void
    {
        $_COOKIE['screen_resolution'] = '1920x1080';

        $client = new Client();

        $this->assertSame('1920x1080', $client->getScreenResolution());
    }

    // --- getToken() ---

    public function testGetTokenReturnsEmptyStringWhenNotSet(): void
    {
        $client = new Client();

        $this->assertSame('', $client->getToken());
    }

    public function testGetTokenReturnsSessionValue(): void
    {
        $_SESSION['token'] = 'abc123';

        $client = new Client();

        $this->assertSame('abc123', $client->getToken());
    }

    // --- getMethod() / getUri() ---
    // NOTE: assumes WebApp::getRequestMethod() reflects $_SERVER['REQUEST_METHOD'].
    // Adjust these two tests if WebApp derives the method differently.

    public function testGetMethodReturnsRequestMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $client = new Client();

        $this->assertSame('POST', $client->getMethod());
    }

    public function testGetUriIncludesPathAndMethod(): void
    {
        $_SERVER['REQUEST_URI'] = '/foo/bar?baz=1';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $client = new Client();

        $this->assertSame('/foo/bar?baz=1 (GET)', $client->getUri());
    }

    public function testGetUriWithEmptyPath(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $client = new Client();

        $this->assertSame(' (GET)', $client->getUri());
    }
}