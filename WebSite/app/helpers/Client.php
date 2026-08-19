<?php

declare(strict_types=1);

namespace app\helpers;

use UAParser\Parser;
use Throwable;

class Client
{
    private string $browser = 'Unknown';
    private string $version = '';
    private string $os = 'Unknown';
    private string $device = 'Unknown';

    public function __construct()
    {
        try {
            $userAgent = To::str($_SERVER['HTTP_USER_AGENT'] ?? '');

            if ($userAgent !== '') {
                $parser = Parser::create();
                $result = $parser->parse($userAgent);

                $this->browser = $result->ua->family;
                $this->version = $result->ua->major ?? '';
                $this->os = $result->os->family;
                $this->device = $result->device->family;
            }
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function getBrowser(): string
    {
        return $this->browser . " " . $this->version;
    }

    public function getOS(): string
    {
        return $this->os;
    }

    public function getType(): string
    {
        return $this->device;
    }

    public function getIp(): string
    {
        return To::str(
            $_SERVER['HTTP_CLIENT_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0'
        );
    }

    public function getMethod(): string
    {
        return WebApp::getRequestMethod();
    }

    public function getReferer(): string
    {
        return To::str($_SERVER['HTTP_REFERER'] ?? '');
    }

    public function getScreenResolution(): string
    {
        return To::str($_COOKIE['screen_resolution'] ?? '');
    }

    public function getToken(): string
    {
        return To::str($_SESSION['token'] ?? '');
    }

    public function getUri(): string
    {
        $uri = To::str($_SERVER['REQUEST_URI'] ?? '');
        $method = WebApp::getRequestMethod();

        return $uri . ' (' . $method . ')';
    }
}
