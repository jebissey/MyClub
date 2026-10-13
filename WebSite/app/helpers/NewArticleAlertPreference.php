<?php

declare(strict_types=1);

namespace app\helpers;

/**
 * @phpstan-type NewArticlePrefs array{
 *     enabled?: bool,
 *     pollOnly?: bool,
 *     orderOnly?: bool,
 *     poll_or_order?: bool
 * }
 * @phpstan-type PersonPreferencesShape array{
 *     eventTypes?: array{
 *         newArticle?: NewArticlePrefs
 *     }
 * }
 */
final class NewArticleAlertPreference
{
    /**
     * Tells whether a member wants to be alerted about a new article, given the
     * member's stored preferences (JSON) and what is attached to the article.
     */
    public static function wantsAlert(?string $preferencesJson, bool $hasSurvey, bool $hasOrder): bool
    {
        if ($preferencesJson === null || $preferencesJson === '') {
            return false;
        }
        /** @var PersonPreferencesShape|null $preferences */
        $preferences = json_decode($preferencesJson, true);
        if (!is_array($preferences) || empty($preferences['eventTypes']['newArticle']['enabled'])) {
            return false;
        }
        $articlePrefs = $preferences['eventTypes']['newArticle'];

        if (isset($articlePrefs['pollOnly'])) {
            return $hasSurvey;
        }
        if (isset($articlePrefs['orderOnly'])) {
            return $hasOrder;
        }
        if (isset($articlePrefs['poll_or_order'])) {
            return $hasSurvey || $hasOrder;
        }
        return true;
    }
}
