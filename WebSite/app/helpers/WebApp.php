<?php

namespace app\helpers;

use RuntimeException;

class WebApp
{
    public function buildUrl($newParams)
    {
        $params = array_merge($_GET, $newParams);
        return '?' . http_build_query($params);
    }

    static public function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . $host . '/';

        return $baseUrl;
    }

    static public function getLayout()
    {
        $navbar = $_SESSION['navbar'] ?? '';
        if ($navbar == 'user') return '../user/user.latte';
        else if ($navbar == 'eventManager') return '../admin/eventManager.latte';
        else if ($navbar == 'personManager') return '../admin/personManager.latte';
        else if ($navbar == 'webmaster') return '../admin/webmaster.latte';
        else if ($navbar == 'redactor') return '../admin/redactor.latte';
        else if ($navbar == '') return '../home.latte';

        throw new RuntimeException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__ . " with navbar=" . $navbar);
    }

    static public function sanitizeHtml($html)
    {
        $allowed_tags = '<div><span><p><br><strong><em><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td>';
        $html = strip_tags($html, $allowed_tags);

        $html = preg_replace('/<(.*?)[\s|>]on[a-z]+=[\'"].*?[\'"]>(.*?)<\/\\1>/i', '<$1>$2</$1>', $html);
        $html = preg_replace('/javascript:.*?[\'"]/i', '', $html);

        return $html;
    }

    static public function sanitizeInput($data)
    {
        return trim($data ?? '');
    }
}
