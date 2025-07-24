<?php

namespace app\helpers;

use UAParser\Parser;

class Client
{
    private $browser;
    private $version;
    private $os;
    private $device;

    public function __construct() {
        $parser = Parser::create();
        $result = $parser->parse($_SERVER['HTTP_USER_AGENT']);

        $this->browser = $result->ua->family;
        $this->version = $result->ua->major;
        $this->os = $result->os->family;
        $this->device = $result->device->family;
    }

    public function getBrowser()
    {
        return $this->browser . " " . $this->version;
    }

    public function getOS()
    {
        return $this->os;
    }

    function getType()
    {
        return $this->device;
    }

    function getIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))           $ip = $_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else                                              $ip = $_SERVER['REMOTE_ADDR'];
        return $ip;
    }

    function getReferer()
    {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    function getScreenResolution()
    {
        if (isset($_COOKIE['screen_resolution'])) $resolution = $_COOKIE['screen_resolution'];
        else $resolution = '';
        return $resolution;
    }

    function getToken()
    {
        return $_SESSION['token'] ?? '';
    }

    function getUri()
    {
        return $_SERVER['REQUEST_URI'];
    }
}
