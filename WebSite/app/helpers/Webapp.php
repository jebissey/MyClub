<?php

namespace app\helpers;

use Latte\Engine as LatteEngine;
use app\helpers\Application;

class Webapp
{
    private LatteEngine $latte;
    protected Application $application;

    public function __construct()
    {
        $this->application = Application::getInstance();
        $this->latte = $this->application->getLatte();
        $this->latte->setTempDirectory(__DIR__ . '/../../var/latte/temp');
        $this->setupLatteFilters();
    }

    static function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . $host . '/';

        return $baseUrl;
    }

    function buildUrl($newParams)
    {
        $params = array_merge($_GET, $newParams);
        return '?' . http_build_query($params);
    }

    static function getLayout()
    {
        $navbar = $_SESSION['navbar'] ?? '';
        if ($navbar == 'user') return '../user/user.latte';
        else if ($navbar == 'eventManager') return '../admin/eventManager.latte';
        else if ($navbar == 'personManager') return '../admin/personManager.latte';
        else if ($navbar == 'webmaster') return '../admin/webmaster.latte';
        else if ($navbar == 'redactor') return '../admin/redactor.latte';
        else if ($navbar == '') return '../home.latte';

        die('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__ . " with navbar=" . $navbar);
    }

    static function sanitizeHtml($html)
    {
        $allowed_tags = '<div><span><p><br><strong><em><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td>';
        $html = strip_tags($html, $allowed_tags);

        $html = preg_replace('/<(.*?)[\s|>]on[a-z]+=[\'"].*?[\'"]>(.*?)<\/\\1>/i', '<$1>$2</$1>', $html);
        $html = preg_replace('/javascript:.*?[\'"]/i', '', $html);

        return $html;
    }

    static function sanitizeInput($data)
    {
        return trim($data ?? '');
    }

    private function setupLatteFilters(): void
    {
        $this->latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension);

        $this->latte->addFilter('json', function ($value) {
            return json_encode($value, JSON_HEX_APOS | JSON_HEX_QUOT);
        });

        $this->latte->addFilter('extractFirstElement', function ($html) {
            if (preg_match('/<p[^>]*>(.*?)<\/p>/s', $html, $matches)) {
                return $matches[0];
            }
            if (preg_match('/<img[^>]*>/i', $html, $matches)) {
                return $matches[0];
            }
            if (preg_match('/<a[^>]*>.*?<\/a>/i', $html, $matches)) {
                return $matches[0];
            }
            $text = strip_tags($html);
            return strlen($text) > 150 ? substr($text, 0, 150) . '...' : $text;
        });

        $this->latte->addFilter('nl2br', function ($string) {
            return nl2br(htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        });

        $this->latte->addFilter('urlencode', function ($s) {
            return urlencode($s);
        });
    }
}
