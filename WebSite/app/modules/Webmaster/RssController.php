<?php

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
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function articlesRssGenerator()
    {
        $base_url = WebApp::getBaseUrl();
        $site_title = "Articles";
        $site_url = $base_url;
        $feed_url = $base_url . "articles-rss.xml";
        $feed_description = "Mises à jour de la liste d'articles";

        $articles = (new ArticleDataHelper($this->application))->getArticlesForRss();

        header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
        header('Pragma: no-cache'); // HTTP 1.0
        header('Expires: 0'); // Proxies
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo $this->generateArticlesRSS($articles, $site_title, $site_url, $feed_url, $feed_description);
    }

    public function eventsRssGenerator()
    {
        $base_url = WebApp::getBaseUrl();
        $site_title = "Événements";
        $site_url = $base_url;
        $feed_url = $base_url . "events-rss.xml";
        $feed_description = "Calendrier des événements et activités du club";

        $weeklyEvents = (new EventDataHelper($this->application))->getNextWeekEvents();
        $events = $this->flattenWeeklyEvents($weeklyEvents);

        header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
        header('Pragma: no-cache'); // HTTP 1.0
        header('Expires: 0'); // Proxies
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo $this->generateEventsRSS($events, $site_title, $site_url, $feed_url, $feed_description);
    }

    #region Private fucntions
    private function flattenWeeklyEvents($weeklyEvents)
    {
        $events = [];
        foreach ($weeklyEvents as $week) {
            foreach ($week['days'] as $day) {
                foreach ($day as $event) {
                    $events[] = (object)$event;
                }
            }
        }
        usort($events, function ($a, $b) {
            return strtotime($a->fullDateTime) - strtotime($b->fullDateTime);
        });
        return $events;
    }

    private function formatEventDescription($event)
    {
        $description = '';
        if (isset($event->summary)) {
            $description .= $event->summary . "\n\n";
        }
        if (isset($event->description) && !empty($event->description)) {
            $description .= $event->description . "\n\n";
        }
        if (isset($event->fullDateTime)) {
            $dateTime = new DateTime($event->fullDateTime);
            $description .= "📅 Date : " . $dateTime->format('d/m/Y') . "\n";
            $description .= "🕐 Heure : " . $dateTime->format('H:i');

            if (isset($event->duration)) {
                $description .= " (durée : " . $event->duration . ")";
            }
            $description .= "\n";
        }
        if (isset($event->location) && !empty($event->location)) {
            $description .= "📍 Lieu : " . $event->location . "\n";
        }
        if (isset($event->eventType)) {
            $description .= "🏷️ Type : " . $event->eventType . "\n";
        }
        if (isset($event->groupName) && !empty($event->groupName)) {
            $description .= "👥 Groupe : " . $event->groupName . "\n";
        }
        if (isset($event->audience)) {
            $audience_text = $event->audience === 'All' ? 'Ouvert à tous' : 'Réservé aux membres';
            $description .= "🎯 Public : " . $audience_text . "\n";
        }
        if (isset($event->attributes) && count($event->attributes) > 0) {
            $description .= "\n🏷️ Informations complémentaires :\n";
            foreach ($event->attributes as $attribute) {
                $description .= "• " . $attribute['name'];
                if (isset($attribute['detail']) && !empty($attribute['detail'])) {
                    $description .= " : " . $attribute['detail'];
                }
                $description .= "\n";
            }
        }
        return trim($description);
    }

    private function generateArticlesRSS($articles, $site_title, $site_url, $feed_url, $feed_description)
    {
        $rss = '<?xml version="1.0" encoding="UTF-8"?>';
        $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
        $rss .= '<channel>';
        $rss .= '<title>' . htmlspecialchars($site_title) . '</title>';
        $rss .= '<link>' . htmlspecialchars($site_url) . '</link>';
        $rss .= '<description>' . htmlspecialchars($feed_description) . '</description>';
        $rss .= '<language>fr-fr</language>';
        $rss .= '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>';
        $rss .= '<atom:link href="' . htmlspecialchars($feed_url) . '" rel="self" type="application/rss+xml" />';

        foreach ($articles as $article) {
            $rss .= '<item>';
            $rss .= '<title>' . htmlspecialchars($article->Title) . '</title>';
            $rss .= '<link>' . htmlspecialchars($site_url . 'articles/' . $article->Id) . '</link>';
            $rss .= '<guid>' . htmlspecialchars($site_url . 'articles/' . $article->Id) . '</guid>';
            $rss .= '<pubDate>' . date(DATE_RSS, strtotime($article->LastUpdate)) . '</pubDate>';
            $rss .= '<description>' . $this->getFirstElement($article->Content) . '</description>';
            $rss .= '</item>';
        }

        $rss .= '</channel>';
        $rss .= '</rss>';

        return $rss;
    }
    private function getFirstElement($html)
    {
        $htmlSansImages = preg_replace('/<img[^>]*>/i', '', $html);
        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $htmlSansImages, $matches)) $text = strip_tags($matches[1]);
        else {
            $text = strip_tags($htmlSansImages);
            $text = trim($text);
        }
        $maxLength = 200;
        if (mb_strlen($text) > $maxLength) $text = mb_substr($text, 0, $maxLength) . '...';
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function generateEventsRSS($events, $site_title, $site_url, $feed_url, $feed_description)
    {
        $rss = '<?xml version="1.0" encoding="UTF-8"?>';
        $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:ev="http://purl.org/rss/1.0/modules/event/">';
        $rss .= '<channel>';
        $rss .= '<title>' . htmlspecialchars($site_title) . '</title>';
        $rss .= '<link>' . htmlspecialchars($site_url) . '</link>';
        $rss .= '<description>' . htmlspecialchars($feed_description) . '</description>';
        $rss .= '<language>fr-fr</language>';
        $rss .= '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>';
        $rss .= '<atom:link href="' . htmlspecialchars($feed_url) . '" rel="self" type="application/rss+xml" />';

        foreach ($events as $event) {
            $rss .= '<item>';
            $rss .= '<title>' . htmlspecialchars($event->summary) . '</title>';

            // URL de l'événement (adaptez selon votre structure)
            $event_url = $site_url . 'contact/event/' . $event->id;
            $rss .= '<link>' . htmlspecialchars($event_url) . '</link>';
            $rss .= '<guid>' . htmlspecialchars($event_url) . '</guid>';

            // Date de publication (utiliser fullDateTime)
            $rss .= '<pubDate>' . date(DATE_RSS, strtotime($event->fullDateTime)) . '</pubDate>';

            // Description enrichie
            $description = $this->formatEventDescription($event);
            $rss .= '<description>' . htmlspecialchars($description) . '</description>';

            // Catégorie si vous avez des types d'événements
            if (isset($event->eventType)) {
                $rss .= '<category>' . htmlspecialchars($event->eventType) . '</category>';
            }

            // Extensions pour les événements (optionnel)
            if (isset($event->fullDateTime)) {
                $rss .= '<ev:startdate>' . date('c', strtotime($event->fullDateTime)) . '</ev:startdate>';
            }

            // Calculer la date de fin avec la durée
            if (isset($event->fullDateTime) && isset($event->duration)) {
                $startDateTime = new DateTime($event->fullDateTime);
                // Extraire les minutes depuis la durée formatée (ex: "2h30", "90min")
                $durationStr = $event->duration;
                $minutes = 0;

                if (strpos($durationStr, 'h') !== false) {
                    if (preg_match('/(\d+)h(\d*)/i', $durationStr, $matches)) {
                        $minutes = $matches[1] * 60 + (int)($matches[2] ?: 0);
                    }
                } elseif (strpos($durationStr, 'min') !== false) {
                    $minutes = (int)str_replace('min', '', $durationStr);
                }

                if ($minutes > 0) {
                    $endDateTime = clone $startDateTime;
                    $endDateTime->add(new DateInterval('PT' . $minutes . 'M'));
                    $rss .= '<ev:enddate>' . $endDateTime->format('c') . '</ev:enddate>';
                }
            }

            if (isset($event->location)) {
                $rss .= '<ev:location>' . htmlspecialchars($event->location) . '</ev:location>';
            }

            $rss .= '</item>';
        }

        $rss .= '</channel>';
        $rss .= '</rss>';

        return $rss;
    }

}
