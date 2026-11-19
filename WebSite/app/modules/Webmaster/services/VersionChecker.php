<?php

declare(strict_types=1);

namespace app\modules\Webmaster\services;

use app\helpers\Application;
use app\helpers\WebApp;

final class VersionChecker
{
    /**
     * Returns null when the application is up to date,
     * otherwise a message describing the new version or the error encountered.
     */
    public function getNewVersionMessage(): ?string
    {
        $url = WebApp::MYCLUB_WEBAPP . 'api/lastVersion?cv=' . Application::VERSION
            . '&url=' . urlencode(WebApp::getBaseUrl());

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => 'PHP/' . PHP_VERSION,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $response = curl_exec($ch);

        if (!is_string($response)) {
            $error = curl_error($ch);
            unset($ch);
            return "Test for MyClub new version error: cURL error ($error)";
        }
        unset($ch);

        $lastVersion = $this->extractLastVersion(json_decode($response, true));
        if ($lastVersion === null) {
            return 'Test for MyClub new version error: invalid API response.';
        }

        return $lastVersion === Application::VERSION
            ? null
            : "A new version is available (V{$lastVersion})";
    }

    private function extractLastVersion(mixed $data): ?string
    {
        if (!is_array($data) || ($data['success'] ?? false) !== true) {
            return null;
        }
        $payload = $data['data'] ?? null;
        if (!is_array($payload) || !is_string($payload['lastVersion'] ?? null)) {
            return null;
        }
        return $payload['lastVersion'];
    }
}
