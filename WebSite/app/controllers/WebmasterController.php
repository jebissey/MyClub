<?php

namespace app\controllers;

use app\helpers\Application;
use app\helpers\ArticleDataHelper;
use app\helpers\ArwardsDataHelper;
use app\helpers\Webapp;

class WebmasterController extends BaseController
{
    public function helpWebmaster(): void
    {
        $this->personDataHelper->getPerson();

        $this->render('app/views/info.latte', [
            'content' => $this->application->getSettings()->get('Help_webmaster'),
            'hasAuthorization' => $this->application->getAuthorizations()->isEventManager(),
            'currentVersion' => Application::getVersion()
        ]);
    }

    public function helpAdmin()
    {
        if ($this->personDataHelper->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {

            $this->render('app/views/info.latte', $this->params->getAll([
                'content' => $this->application->getSettings()->get('Help_admin'),
                'hasAuthorization' => $this->application->getAuthorizations()->isEventManager(),
                'currentVersion' => $this->application->getVersion()
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function homeWebmaster(): void
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'webmaster';

                $newVersion = null;
                $result = $this->getLastVersion();
                if (!$result['success']) error_log("Erreur récupération version : " . $result['error']);
                elseif ($result['version'] != $this->application->getVersion()) $newVersion = "A new version is available (V" . $result['version'] . ")";

                $this->render('app/views/admin/webmaster.latte', $this->params->getAll(['newVersion' => $newVersion]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function homeAdmin()
    {
        if ($this->personDataHelper->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {
            if ($this->application->getAuthorizations()->hasOnlyOneAutorization()) {
                if ($this->application->getAuthorizations()->isEventManager())       $this->flight->redirect('/eventManager');
                else if ($this->application->getAuthorizations()->isPersonManager()) $this->flight->redirect('/personManager');
                else if ($this->application->getAuthorizations()->isRedactor()) {
                    $_SESSION['navbar'] = 'redactor';
                    $this->flight->redirect('/articles');
                } else if ($this->application->getAuthorizations()->isWebmaster())   $this->flight->redirect('/webmaster');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') $this->render('app/views/admin/admin.latte', $this->params->getAll([]));
            else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function arwards(): void
    {
        if ($person = $this->personDataHelper->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $arwardsDataHelper = new ArwardsDataHelper();
                $this->render('app/views/admin/arwards.latte', $this->params->getAll([
                    'counterNames' => $counterNames = $arwardsDataHelper->getCounterNames(),
                    'data' => $arwardsDataHelper->getData($counterNames),
                    'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                    'layout' => Webapp::getLayout()(),
                    'navItems' => $this->getNavItems($person),
                ]));
            } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $request = $this->flight->request();
                $name = $request->data->customName ?? $request->data->name;
                $detail = $request->data->detail;
                $value = (int)$request->data->value;
                $idPerson = (int)$request->data->idPerson;
                $idGroup = (int)$request->data->idGroup;

                if (empty($name) || $value < 0 || $idPerson <= 0 || $idGroup <= 0) $this->flight->redirect('/arwards?error=invalid_data');
                else {
                    $this->dataHelper->set('Counter', [
                        'Name' => $name,
                        'Detail' => $detail,
                        'Value' => $value,
                        'IdPerson' => $idPerson,
                        'IdGroup' => $idGroup
                    ]);
                    $this->flight->redirect('/arwards?success=true');
                }
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function rssGenerator()
    {
        $base_url = Webapp::getBaseUrl();
        $site_title = "Liste d'articles";
        $site_url = $base_url;
        $feed_url = $base_url . "rss.xml";
        $feed_description = "Mises à jour de la liste d'articles";
        $articles = (new ArticleDataHelper())->getArticlesForRss(($this->personDataHelper->getPerson([]))->Id ?? 0);
        header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
        header('Pragma: no-cache'); // HTTP 1.0
        header('Expires: 0'); // Proxies
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo $this->generateRSS($articles, $site_title, $site_url, $feed_url, $feed_description);
    }

    public function sitemapGenerator()
    {
        $articleDataHelper = new ArticleDataHelper();
        $base_url = Webapp::getBaseUrl();
        header("Content-Type: application/xml; charset=utf-8");
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        echo '  <url>' . PHP_EOL;
        echo '    <loc>' . $base_url . '</loc>' . PHP_EOL;
        echo '    <lastmod>' . $articleDataHelper->getLastUpdateArticles() . '</lastmod>' . PHP_EOL;
        echo '    <changefreq>daily</changefreq>' . PHP_EOL;
        echo '    <priority>1.0</priority>' . PHP_EOL;
        echo '  </url>' . PHP_EOL;

        $articles = $articleDataHelper->getArticlesForAll();
        foreach ($articles as $article) {
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . $base_url . '/article/' . $article->Id . '</loc>' . PHP_EOL;
            echo '    <lastmod>' . $article->LastUpdate . '</lastmod>' . PHP_EOL;
            echo '    <changefreq>monthly</changefreq>' . PHP_EOL;
            echo '    <priority>0.5</priority>' . PHP_EOL;
            echo '  </url>' . PHP_EOL;
        }
        echo '</urlset>';
    }

    #region Private methods
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
        $url = "http://myclub.alwaysdata.net/api/lastVersion";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "PHP/" . PHP_VERSION);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'success' => false,
                'version' => null,
                'error'   => "Erreur cURL : $error"
            ];
        }

        curl_close($ch);
        $data = json_decode($response, true);
        if ($data === null || !isset($data["lastVersion"])) {
            return [
                'success' => false,
                'version' => null,
                'error'   => "Réponse JSON invalide ou champ 'lastVersion' absent."
            ];
        }

        return [
            'success' => true,
            'version' => $data["lastVersion"],
            'error'   => null
        ];
    }
}
