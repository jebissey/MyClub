<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use DateInterval;
use DateTime;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\EventDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\valueObjects\ArticleRssRow;
use app\modules\Common\valueObjects\EventRow;

/**
 * @phpstan-import-type WeekData from EventDataHelper
 */
final class RssController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly ArticleDataHelper $articleDataHelper,
        private readonly EventDataHelper $eventDataHelper
    ) {
        parent::__construct($application);
    }

    public function articlesRssGenerator(): void
    {
        $baseUrl         = WebApp::getBaseUrl();
        $siteTitle       = "Articles";
        $siteUrl         = $baseUrl;
        $feedUrl         = $baseUrl . "articles-rss.xml";
        $feedDescription = "Mises à jour de la liste d'articles";

        $articles = $this->articleDataHelper->getArticlesForRss();

        $this->sendRssHeaders();
        echo $this->generateArticlesRSS($articles, $siteTitle, $siteUrl, $feedUrl, $feedDescription);
    }

    public function eventsRssGenerator(): void
    {
        $baseUrl         = WebApp::getBaseUrl();
        $siteTitle       = "Événements";
        $siteUrl         = $baseUrl;
        $feedUrl         = $baseUrl . "events-rss.xml";
        $feedDescription = "Calendrier des événements et activités du club";

        $weeklyEvents = $this->eventDataHelper->getNextWeekEvents();
        $events       = $this->flattenWeeklyEvents($weeklyEvents);

        $this->sendRssHeaders();
        echo $this->generateEventsRSS($events, $siteTitle, $siteUrl, $feedUrl, $feedDescription);
    }

    private function sendRssHeaders(): void
    {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Type: application/rss+xml; charset=utf-8');
    }

    private function getFirstElement(string $html): string
    {
        $htmlSansImages = preg_replace('/<img[^>]*>/i', '', $html) ?? $html;

        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $htmlSansImages, $matches)) {
            $text = strip_tags($matches[1]);
        } else {
            $text = strip_tags($htmlSansImages);
        }

        $text = html_entity_decode(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (mb_strlen($text) > 200) {
            $text = mb_substr($text, 0, 200) . '…';
        }

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param list<ArticleRssRow> $articles
     */
    private function generateArticlesRSS(
        array $articles,
        string $siteTitle,
        string $siteUrl,
        string $feedUrl,
        string $feedDescription
    ): string {
        $rss  = '<?xml version="1.0" encoding="UTF-8"?>';
        $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
        $rss .= '<channel>';
        $rss .= '<title>'       . htmlspecialchars($siteTitle, ENT_XML1, 'UTF-8') . '</title>';
        $rss .= '<link>'        . htmlspecialchars($siteUrl, ENT_XML1, 'UTF-8') . '</link>';
        $rss .= '<description>' . htmlspecialchars($feedDescription, ENT_XML1, 'UTF-8') . '</description>';
        $rss .= '<language>fr-fr</language>';
        $rss .= '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>';
        $rss .= '<atom:link href="' . htmlspecialchars($feedUrl, ENT_XML1, 'UTF-8') . '" rel="self" type="application/rss+xml" />';

        foreach ($articles as $article) {
            $lastUpdateTimestamp = strtotime($article->lastUpdate);
            if ($lastUpdateTimestamp === false) {
                $lastUpdateTimestamp = time();
            }
            $guid        = $siteUrl . 'article/' . $article->id . '?v=' . $lastUpdateTimestamp;
            $description = $this->getFirstElement($article->content);
            $pubDate     = date(DATE_RSS, $lastUpdateTimestamp);
            $atomUpdated = (new DateTime($article->lastUpdate))->format(DateTime::ATOM);

            $rss .= '<item>';
            $rss .= '<title>'       . htmlspecialchars($article->title, ENT_XML1, 'UTF-8') . '</title>';
            $rss .= '<link>'        . htmlspecialchars($guid, ENT_XML1, 'UTF-8') . '</link>';
            $rss .= '<guid isPermaLink="false">' . htmlspecialchars($guid, ENT_XML1, 'UTF-8') . '</guid>';
            $rss .= '<pubDate>'     . $pubDate     . '</pubDate>';
            $rss .= '<atom:updated>' . $atomUpdated . '</atom:updated>';
            $rss .= '<description>' . $description . '</description>';
            $rss .= '</item>';
        }

        $rss .= '</channel>';
        $rss .= '</rss>';

        return $rss;
    }

    /**
     * @param array<string, WeekData> $weeklyEvents
     * @return list<EventRow>
     */
    private function flattenWeeklyEvents(array $weeklyEvents): array
    {
        $events = [];

        foreach ($weeklyEvents as $week) {
            foreach ($week['days'] as $day) {
                foreach ($day as $event) {
                    $events[] = EventRow::fromArray($event);
                }
            }
        }

        usort(
            $events,
            static fn(EventRow $a, EventRow $b): int =>
            strtotime($a->fullDateTime) <=> strtotime($b->fullDateTime)
        );

        return $events;
    }

    private function formatEventDescription(EventRow $event): string
    {
        $parts = [];

        if (!empty($event->summary)) {
            $parts[] = $event->summary;
        }
        if (!empty($event->description)) {
            $parts[] = $event->description;
        }
        if (!empty($event->fullDateTime)) {
            $dt = new DateTime($event->fullDateTime);
            $line = "📅 Date : " . $dt->format('d/m/Y') . "\n🕐 Heure : " . $dt->format('H:i');
            if (!empty($event->duration)) {
                $line .= " (durée : {$event->duration})";
            }
            $parts[] = $line;
        }
        if (!empty($event->location)) {
            $parts[] = "📍 Lieu : {$event->location}";
        }
        if (!empty($event->eventType)) {
            $parts[] = "🏷️ Type : {$event->eventType}";
        }
        if (!empty($event->groupName)) {
            $parts[] = "👥 Groupe : {$event->groupName}";
        }
        if (isset($event->audience)) {
            $audienceText = $event->audience === 'All' ? 'Ouvert à tous' : 'Réservé aux membres';
            $parts[] = "🎯 Public : {$audienceText}";
        }
        if (!empty($event->attributes)) {
            $attrLines = ["🏷️ Informations complémentaires :"];
            foreach ($event->attributes as $attribute) {
                $attrLine = "• " . $attribute->name;
                if (!empty($attribute->detail)) {
                    $attrLine .= " : " . $attribute->detail;
                }
                $attrLines[] = $attrLine;
            }
            $parts[] = implode("\n", $attrLines);
        }

        return implode("\n\n", $parts);
    }

    /**
     * @param list<EventRow> $events
     */
    private function generateEventsRSS(
        array $events,
        string $siteTitle,
        string $siteUrl,
        string $feedUrl,
        string $feedDescription
    ): string {
        $rss  = '<?xml version="1.0" encoding="UTF-8"?>';
        $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:ev="http://purl.org/rss/1.0/modules/event/">';
        $rss .= '<channel>';
        $rss .= '<title>' . htmlspecialchars($siteTitle, ENT_XML1, 'UTF-8') . '</title>';
        $rss .= '<link>' . htmlspecialchars($siteUrl, ENT_XML1, 'UTF-8') . '</link>';
        $rss .= '<description>' . htmlspecialchars($feedDescription, ENT_XML1, 'UTF-8') . '</description>';
        $rss .= '<language>fr-fr</language>';
        $rss .= '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>';
        $rss .= '<atom:link href="' . htmlspecialchars($feedUrl, ENT_XML1, 'UTF-8') . '" rel="self" type="application/rss+xml" />';

        foreach ($events as $event) {
            $eventUrl = $siteUrl . 'contact/event/' . $event->id;

            $rss .= '<item>';
            $rss .= '<title>' . htmlspecialchars($event->summary, ENT_XML1, 'UTF-8') . '</title>';
            $rss .= '<link>' . htmlspecialchars($eventUrl, ENT_XML1, 'UTF-8') . '</link>';
            $rss .= '<guid isPermaLink="true">' . htmlspecialchars($eventUrl, ENT_XML1, 'UTF-8') . '</guid>';
            $rss .= '<pubDate>' . (new DateTime($event->fullDateTime))->format(DATE_RSS) . '</pubDate>';
            $rss .= '<description>' . htmlspecialchars(
                $this->formatEventDescription($event),
                ENT_XML1,
                'UTF-8'
            ) . '</description>';

            if ($event->eventType !== null && $event->eventType !== '') {
                $rss .= '<category>' . htmlspecialchars($event->eventType, ENT_XML1, 'UTF-8') . '</category>';
            }

            $rss .= '<ev:startdate>' . (new DateTime($event->fullDateTime))->format('c') . '</ev:startdate>';

            if ($event->duration !== null && $event->duration !== '') {
                $endDateTime = $this->computeEndDateTime($event->fullDateTime, $event->duration);

                if ($endDateTime !== null) {
                    $rss .= '<ev:enddate>' . $endDateTime->format('c') . '</ev:enddate>';
                }
            }

            if ($event->location !== null && $event->location !== '') {
                $rss .= '<ev:location>' . htmlspecialchars($event->location, ENT_XML1, 'UTF-8') . '</ev:location>';
            }

            $rss .= '</item>';
        }

        $rss .= '</channel>';
        $rss .= '</rss>';

        return $rss;
    }

    private function computeEndDateTime(string $startStr, string $durationStr): ?DateTime
    {
        $minutes = 0;

        if (preg_match('/(\d+)h(\d*)/i', $durationStr, $m)) {
            $minutes = (int)$m[1] * 60 + (int)($m[2] ?: 0);
        } elseif (preg_match('/(\d+)\s*min/i', $durationStr, $m)) {
            $minutes = (int)$m[1];
        } elseif (ctype_digit(trim($durationStr))) {
            $minutes = (int)$durationStr;
        }

        if ($minutes <= 0) {
            return null;
        }

        $end = new DateTime($startStr);
        $end->add(new DateInterval('PT' . $minutes . 'M'));
        return $end;
    }
}
