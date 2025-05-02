<?php

namespace app\controllers;

use Exception;
use PDO;
use app\helpers\Arwards;

class WebmasterController extends BaseController
{
    public function helpWebmaster(): void
    {
        $this->getPerson();

        echo $this->latte->render('app/views/info.latte', [
            'content' => $this->settings->get('Help_webmaster'),
            'hasAuthorization' => $this->authorizations->hasAutorization(),
            'currentVersion' => self::VERSION
        ]);
    }

    public function helpAdmin()
    {
        if ($this->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {

            echo $this->latte->render('app/views/info.latte', $this->params->getAll([
                'content' => $this->settings->get('Help_admin'),
                'hasAuthorization' => $this->authorizations->isEventManager(),
                'currentVersion' => self::VERSION
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function homeWebmaster(): void
    {
        if ($this->getPerson(['Webmaster'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'webmaster';

                $newVersion = null;
                if ($lastVersion = $this->getLastVersion()) {
                    if ($lastVersion != self::VERSION) {
                        $newVersion = "A new version is available (V$lastVersion)";
                    }
                }
                echo $this->latte->render('app/views/admin/webmaster.latte', $this->params->getAll(['newVersion' => $newVersion]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function homeAdmin()
    {
        if ($this->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {
            if ($this->authorizations->hasOnlyOneAutorization()) {
                if ($this->authorizations->isEventManager()) {
                    $this->flight->redirect('/eventManager');
                } else if ($this->authorizations->isPersonManager()) {
                    $this->flight->redirect('/personManager');
                } else if ($this->authorizations->isRedactor()) {
                    $this->flight->redirect('/articles');
                } else if ($this->authorizations->isWebmaster()) {
                    $this->flight->redirect('/webmaster');
                }
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                echo $this->latte->render('app/views/admin/admin.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function arwards(): void
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $arwards = new Arwards($this->pdo);
                echo $this->latte->render('app/views/admin/arwards.latte', $this->params->getAll([
                    'counterNames' => $counterNames = $arwards->getCounterNames(),
                    'data' => $arwards->getData($counterNames),
                    'groups' => $this->getGroups(),
                    'layout' => $this->getLayout()
                ]));
            } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $request = $this->flight->request();
                $name = $request->data->customName ?? $request->data->name;
                $detail = $request->data->detail;
                $value = (int)$request->data->value;
                $idPerson = (int)$request->data->idPerson;
                $idGroup = (int)$request->data->idGroup;

                if (empty($name) || $value < 0 || $idPerson <= 0 || $idGroup <= 0) {
                    $this->flight->redirect('/arwards?error=invalid_data');
                } else {
                    try {
                        $this->fluent->insertInto('Counter')
                            ->values([
                                'Name' => $name,
                                'Detail' => $detail,
                                'Value' => $value,
                                'IdPerson' => $idPerson,
                                'IdGroup' => $idGroup
                            ])
                            ->execute();
                        $this->flight->redirect('/arwards?success=true');
                    } catch (\Exception $e) {
                        $this->flight->redirect('/arwards?error=' . urlencode($e->getMessage()));
                    }
                }
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function rssGenerator()
    {
        $base_url = $this->getBaseUrl();
        $site_title = "Liste d'articles";
        $site_url = $base_url;
        $feed_url = $base_url . "/rss.xml";
        $feed_description = "Mises à jour de la liste d'articles";

        $personId = ($this->getPerson([]))['Id'] ?? 0;
        $query = $this->pdo->query("
            SELECT DISTINCT Article.*
            FROM Article
            CROSS JOIN Person p
            LEFT JOIN PersonGroup pg ON pg.IdPerson = p.Id
            WHERE Article.PublishedBy IS NOT NULL
            AND ((Article.IdGroup IS NULL AND Article.OnlyForMembers = 0)
              OR (Article.IdGroup IS NULL AND Article.OnlyForMembers = 1 AND $personId <> 0)
              OR (Article.IdGroup IS NOT NULL AND Article.IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE PersonGroup.IdPerson = $personId))
            )
            ORDER BY Article.LastUpdate DESC");
        $articles = $query->fetchAll(PDO::FETCH_ASSOC);

        header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
        header('Pragma: no-cache'); // HTTP 1.0
        header('Expires: 0'); // Proxies

        header('Content-Type: application/rss+xml; charset=utf-8');
        echo $this->generateRSS($articles, $site_title, $site_url, $feed_url, $feed_description);
    }

    public function sitemapGenerator()
    {

        $base_url = $this->getBaseUrl();
        $lastMod = $this->fluent->from('Article')
            ->select(null)
            ->select('MAX(LastUpdate) AS LastMod')
            ->fetch('LastMod');

        header("Content-Type: application/xml; charset=utf-8");
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . $base_url . '/</loc>' . PHP_EOL;
        echo '    <lastmod>' . $lastMod . '</lastmod>' . PHP_EOL;
        echo '    <changefreq>daily</changefreq>' . PHP_EOL;
        echo '    <priority>1.0</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;
        echo '</urlset>';
    }


    private function generateRSS($articles, $site_title, $site_url, $feed_url, $feed_description)
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
            $rss .= '<title>' . htmlspecialchars($article['Title']) . '</title>';
            $rss .= '<link>' . htmlspecialchars($site_url . '/articles/' . $article['Id']) . '</link>';
            $rss .= '<guid>' . htmlspecialchars($site_url . '/articles/' . $article['Id']) . '</guid>';
            $rss .= '<pubDate>' . date(DATE_RSS, strtotime($article['LastUpdate'])) . '</pubDate>';
            $rss .= '<description>' . $this->getFirstElement($article['Content']) . '</description>';
            $rss .= '</item>';
        }

        $rss .= '</channel>';
        $rss .= '</rss>';

        return $rss;
    }

    private function getFirstElement($html)
    {
        $htmlSansImages = preg_replace('/<img[^>]*>/i', '', $html);
        if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $htmlSansImages, $matches)) {
            $text = strip_tags($matches[1]);
        } else {
            $text = strip_tags($htmlSansImages);
            $text = trim($text);
        }

        $maxLength = 200;
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength) . '...';
        }
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function getLastVersion()
    {
        $options = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: PHP/" . PHP_VERSION . "\r\nAccept: application/json\r\n",
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];
        $url = "https://myclub.alwaysdata.net/api/lastVersion";
        $response = file_get_contents($url, false, stream_context_create($options));

        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data !== null) {
                return $data["lastVersion"];
            }
        }
        return false;
    }

    protected function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $domain = $_SERVER['HTTP_HOST'];
        return $protocol . $domain;
    }
}
