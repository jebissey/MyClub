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

class RssController extends AbstractController
{
    public function __construct(
        Application $application,
        private ArticleDataHelper $articleDataHelper,
        private EventDataHelper $eventDataHelper
    ) {
        parent::__construct($application);
    }

    public function articlesRssGenerator(): void
    {
        $base_url         = WebApp::getBaseUrl();
        $site_title       = "Articles";
        $site_url         = $base_url;
        $feed_url         = $base_url . "articles-rss.xml";
        $feed_description = "Mises à jour de la liste d'articles";

        $articles = $this->articleDataHelper->getArticlesForRss();

        $this->sendRssHeaders();
        echo $this->generateArticlesRSS($articles, $site_title, $site_url, $feed_url, $feed_description);
    }

    public function eventsRssGenerator(): void
    {
        $base_url         = WebApp::getBaseUrl();
        $site_title       = "Événements";
        $site_url         = $base_url;
        $feed_url         = $base_url . "events-rss.xml";
        $feed_description = "Calendrier des événements et activités du club";

        $weeklyEvents = $this->eventDataHelper->getNextWeekEvents();
        $events       = $this->flattenWeeklyEvents($weeklyEvents);

        $this->sendRssHeaders();
        echo $this->generateEventsRSS($events, $site_title, $site_url, $feed_url, $feed_description);
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
        $htmlSansImages = preg_replace('/<img[^>]*>/i', '', $html);

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

    private function generateArticlesRSS(
        array  $articles,
        string $site_title,
        string $site_url,
        string $feed_url,
        string $feed_description
    ): string {
        $rss  = '<?xml version="1.0" encoding="UTF-8"?>';
        $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
        $rss .= '<channel>';
        $rss .= '<title>'       . htmlspecialchars($site_title,       ENT_XML1, 'UTF-8') . '</title>';
        $rss .= '<link>'        . htmlspecialchars($site_url,          ENT_XML1, 'UTF-8') . '</link>';
        $rss .= '<description>' . htmlspecialchars($feed_description,  ENT_XML1, 'UTF-8') . '</description>';
        $rss .= '<language>fr-fr</language>';
        $rss .= '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>';
        $rss .= '<atom:link href="' . htmlspecialchars($feed_url, ENT_XML1, 'UTF-8') . '" rel="self" type="application/rss+xml" />';

        foreach ($articles as $article) {
            $guid        = $site_url . 'article/' . $article->Id;
            $description = $this->getFirstElement($article->Content ?? '');
            $pubDate     = date(DATE_RSS, strtotime($article->CreationDate));
            $atomUpdated = (new DateTime($article->LastUpdate))->format(DateTime::ATOM);

            $rss .= '<item>';
            $rss .= '<title>'       . htmlspecialchars($article->Title, ENT_XML1, 'UTF-8') . '</title>';
            $rss .= '<link>'        . htmlspecialchars($guid,           ENT_XML1, 'UTF-8') . '</link>';
            $rss .= '<guid isPermaLink="true">' . htmlspecialchars($guid, ENT_XML1, 'UTF-8') . '</guid>';
            $rss .= '<pubDate>'     . $pubDate     . '</pubDate>';
            $rss .= '<atom:updated>' . $atomUpdated . '</atom:updated>';
            $rss .= '<description>' . $description . '</description>';
            $rss .= '</item>';
        }

        $rss .= '</channel>';
        $rss .= '</rss>';

        return $rss;
    }

    private function flattenWeeklyEvents(array $weeklyEvents): array
    {
        $events = [];
        foreach ($weeklyEvents as $week) {
            foreach ($week['days'] as $day) {
                foreach ($day as $event) {
                    $events[] = (object)$event;
                }
            }
        }
        usort($events, fn($a, $b) => strtotime($a->fullDateTime) - strtotime($b->fullDateTime));
        return $events;
    }

    private function formatEventDescription(object $event): string
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
                $attrLine = "• " . $attribute['name'];
                if (!empty($attribute['detail'])) {
                    $attrLine .= " : " . $attribute['detail'];
                }
                $attrLines[] = $attrLine;
            }
            $parts[] = implode("\n", $attrLines);
        }

        return implode("\n\n", $parts);
    }

    private function generateEventsRSS(
        array  $events,
        string $site_title,
        string $site_url,
        string $feed_url,
        string $feed_description
    ): string {
        $rss  = '<?xml version="1.0" encoding="UTF-8"?>';
        $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:ev="http://purl.org/rss/1.0/modules/event/">';
        $rss .= '<channel>';
        $rss .= '<title>'       . htmlspecialchars($site_title,       ENT_XML1, 'UTF-8') . '</title>';
        $rss .= '<link>'        . htmlspecialchars($site_url,          ENT_XML1, 'UTF-8') . '</link>';
        $rss .= '<description>' . htmlspecialchars($feed_description,  ENT_XML1, 'UTF-8') . '</description>';
        $rss .= '<language>fr-fr</language>';
        $rss .= '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>';
        $rss .= '<atom:link href="' . htmlspecialchars($feed_url, ENT_XML1, 'UTF-8') . '" rel="self" type="application/rss+xml" />';

        foreach ($events as $event) {
            $event_url  = $site_url . 'contact/event/' . $event->id;
            $description = $this->formatEventDescription($event);

            $rss .= '<item>';
            $rss .= '<title>'       . htmlspecialchars($event->summary, ENT_XML1, 'UTF-8') . '</title>';
            $rss .= '<link>'        . htmlspecialchars($event_url,      ENT_XML1, 'UTF-8') . '</link>';
            $rss .= '<guid isPermaLink="true">' . htmlspecialchars($event_url, ENT_XML1, 'UTF-8') . '</guid>';
            $rss .= '<pubDate>'     . date(DATE_RSS, strtotime($event->fullDateTime)) . '</pubDate>';
            $rss .= '<description>' . htmlspecialchars($description, ENT_XML1, 'UTF-8') . '</description>';

            if (!empty($event->eventType)) {
                $rss .= '<category>' . htmlspecialchars($event->eventType, ENT_XML1, 'UTF-8') . '</category>';
            }
            if (!empty($event->fullDateTime)) {
                $rss .= '<ev:startdate>' . date('c', strtotime($event->fullDateTime)) . '</ev:startdate>';
            }
            if (!empty($event->fullDateTime) && !empty($event->duration)) {
                $endDateTime = $this->computeEndDateTime($event->fullDateTime, $event->duration);
                if ($endDateTime !== null) {
                    $rss .= '<ev:enddate>' . $endDateTime->format('c') . '</ev:enddate>';
                }
            }
            if (!empty($event->location)) {
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
