<?php

namespace app\helpers;

use DateTime;
use IntlDateFormatter;

use app\enums\WeekdayFormat;

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

    public static function setLanguage($language)
    {
        $language = in_array($language, self::SUPPORTED_LANGUAGES) ? $language : self::DEFAULT_LANGUAGE;

        setcookie(self::COOKIE_NAME, $language, time() + (self::COOKIE_EXPIRATION), self::COOKIE_PATH);
        $_COOKIE[self::COOKIE_NAME] = $language;
        header('Location: ' . $_SERVER['PHP_SELF']);
    }

    public static function getCurrentLanguage()
    {
        return $_COOKIE['user_language'] ?? self::DEFAULT_LANGUAGE;
    }

    public static function getSupportedLanguages()
    {
        return self::SUPPORTED_LANGUAGES;
    }

    public static function getFlag(string $locale): string
    {
        return self::FLAGS[$locale] ?? '🏳️';
    }

    public static function getShortDate($date)
    {
        $formatter = new IntlDateFormatter(self::getCurrentLanguage(), IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
        return $formatter->format(new DateTime($date));
    }

    public static function getLongDate($date)
    {
        $formatter = new IntlDateFormatter(self::getCurrentLanguage(), IntlDateFormatter::FULL, IntlDateFormatter::NONE);
        return $formatter->format(new DateTime($date));
    }

    public static function getLongDateTime($date)
    {
        $formatter = new IntlDateFormatter(self::getCurrentLanguage(), IntlDateFormatter::FULL, IntlDateFormatter::SHORT);
        return $formatter->format(new DateTime($date));
    }

    public static function getReadableDuration($duration)
    {
        $durationHours = floor($duration / 3600);
        $durationMinutes = floor(($duration % 3600) / 60);
        return ($durationHours > 0 ? "$durationHours h " : '') . ($durationMinutes > 0 ? "$durationMinutes min" : '');
    }

    public static function getWeekdayNames(WeekdayFormat $format = WeekdayFormat::Full): array
    {
        $formatter = new IntlDateFormatter(self::getCurrentLanguage(), IntlDateFormatter::FULL, IntlDateFormatter::NONE);
        $formatter->setPattern($format->value);
        $days = [];
        for ($i = 1; $i <= 7; $i++) {
            $date = new DateTime();
            $date->setISODate(2024, 1, $i); //2024-01-01 = Monday
            $days[] = $formatter->format($date);
        }
        return $days;
    }
}
