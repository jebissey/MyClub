<?php

namespace app\helpers;

use UAParser\Parser;
use Throwable;

class Client
{
    private $browser = 'Unknown';
    private $version = '';
    private $os = 'Unknown';
    private $device = 'Unknown';

    public function __construct()
    {
        try {
            if (!empty($_SERVER['HTTP_USER_AGENT'])) {
                $parser = Parser::create();
                $result = $parser->parse($_SERVER['HTTP_USER_AGENT']);

                $this->browser = $result->ua->family ?? 'Unknown';
                $this->version = $result->ua->major ?? '';
                $this->os = $result->os->family ?? 'Unknown';
                $this->device = $result->device->family ?? 'Unknown';
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
        return $_SERVER['HTTP_CLIENT_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    public function getReferer(): string
    {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    public function getScreenResolution(): string
    {
        return $_COOKIE['screen_resolution'] ?? '';
    }

    public function getToken(): string
    {
        return $_SESSION['token'] ?? '';
    }

    public function getUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }
}
