<?php

namespace app\utils;

use DateTime;
use IntlDateFormatter;

class TranslationManager
{
    const SUPPORTED_LANGUAGES = ['en_US', 'fr_FR'];
    const FLAGS = [
        'en_US' => '🇺🇸',
        'fr_FR' => '🇫🇷',
    ];
    const DEFAULT_LANGUAGE = 'fr_FR';
    const COOKIE_NAME = 'user_language';
    const COOKIE_EXPIRATION = 86400 * 30; // 30 days
    const COOKIE_PATH = '/';

    static function setLanguage($language)
    {
        $language = in_array($language, self::SUPPORTED_LANGUAGES) ? $language : self::DEFAULT_LANGUAGE;

        setcookie(self::COOKIE_NAME, $language, time() + (self::COOKIE_EXPIRATION), self::COOKIE_PATH);
        $_COOKIE[self::COOKIE_NAME] = $language;
        header('Location: ' . $_SERVER['PHP_SELF']);
    }

    static function getCurrentLanguage()
    {
        return $_COOKIE['user_language'] ?? self::DEFAULT_LANGUAGE;
    }

    static function getSupportedLanguages()
    {
        return self::SUPPORTED_LANGUAGES;
    }

    static function getFlag(string $locale): string
    {
        return self::FLAGS[$locale] ?? '🏳️';
    }

    static function getShortDate($date)
    {
        $formatter = new IntlDateFormatter(self::getCurrentLanguage(), IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
        return $formatter->format(new DateTime($date));
    }

    static function getLongDate($date)
    {
        $formatter = new IntlDateFormatter(self::getCurrentLanguage(), IntlDateFormatter::FULL, IntlDateFormatter::NONE);
        return $formatter->format(new DateTime($date));
    }

    static function getLongDateTime($date)
    {
        $formatter = new IntlDateFormatter(self::getCurrentLanguage(), IntlDateFormatter::FULL, IntlDateFormatter::SHORT);
        return $formatter->format(new DateTime($date));
    }

    static function getReadableDuration($duration)
    {
        $durationHours = floor($duration / 3600);
        $durationMinutes = floor(($duration % 3600) / 60);
        return ($durationHours > 0 ? "$durationHours h " : '') . ($durationMinutes > 0 ? "$durationMinutes min" : '');
    }
}
