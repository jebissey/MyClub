<?php

namespace app\helpers;

use DateTime;
use IntlDateFormatter;
use PDO;

class TranslationManager
{
    private $fluent;
    const SUPPORTED_LANGUAGES = ['en_US', 'fr_FR'];
    const FLAGS = [
        'en_US' => '🇺🇸',
        'fr_FR' => '🇫🇷',
    ];
    const DEFAULT_LANGUAGE = 'fr_FR';
    const COOKIE_NAME = 'user_language';
    const COOKIE_EXPIRATION = 86400 * 30; // 30 days
    const COOKIE_PATH = '/';

    public function __construct(PDO $pdo)
    {
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
    }

    public function setLanguage($language)
    {
        $language = in_array($language, self::SUPPORTED_LANGUAGES) ? $language : self::DEFAULT_LANGUAGE;

        setcookie(self::COOKIE_NAME, $language, time() + (self::COOKIE_EXPIRATION), self::COOKIE_PATH);
        $_COOKIE[self::COOKIE_NAME] = $language;
        header('Location: ' . $_SERVER['PHP_SELF']);
    }

    public function getCurrentLanguage()
    {
        return $_COOKIE['user_language'] ?? self::DEFAULT_LANGUAGE;
    }

    public function translate($key)
    {
        $translation = $this->fluent->from('Languages')->where('Name', $key)->fetch($this->getCurrentLanguage());
        return !$translation ? "-- $key --" : $translation;
    }

    public function getSupportedLanguages()
    {
        return self::SUPPORTED_LANGUAGES;
    }

    public function getFlag(string $locale): string
    {
        return self::FLAGS[$locale] ?? '🏳️';
    }


    public function getShortDate($date)
    {
        $formatter = new IntlDateFormatter($this->getCurrentLanguage(), IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
        return $formatter->format(new DateTime($date));
    }

    public function getLongDate($date)
    {
        $formatter = new IntlDateFormatter($this->getCurrentLanguage(), IntlDateFormatter::FULL, IntlDateFormatter::NONE);
        return $formatter->format(new DateTime($date));
    }

    public function getLongDateTime($date)
    {
        $formatter = new IntlDateFormatter($this->getCurrentLanguage(), IntlDateFormatter::FULL, IntlDateFormatter::SHORT);
        return $formatter->format(new DateTime($date));
    }

    public function getReadableDuration($duration)
    {
        $durationHours = floor($duration / 3600);
        $durationMinutes = floor(($duration % 3600) / 60);
        return ($durationHours > 0 ? "$durationHours h " : '') . ($durationMinutes > 0 ? "$durationMinutes min" : '');
    }
}
