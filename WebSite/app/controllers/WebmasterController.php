<?php

namespace app\controllers;

use Exception;
use app\helpers\Arwards;

class WebmasterController extends BaseController
{
    public function help(): void
    {
        $this->getPerson();

        echo $this->latte->render('app/views/info.latte', [
            'content' => $this->settings->get('Help_webmaster'),
            'hasAuthorization' => $this->authorizations->hasAutorization(),
            'currentVersion' => self::VERSION
        ]);
    }

    public function home(): void
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

    public function arwards(): void
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $arwards = new Arwards($this->pdo);
                echo $this->latte->render('app/views/admin/arwards.latte', $this->params->getAll([
                    'counterNames' => $counterNames = $arwards->getCounterNames(),
                    'data' => $arwards->getData($counterNames),
                    'groups' => $arwards->getGroups(),
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
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $domain = $_SERVER['HTTP_HOST'];
        $base_url = $protocol . $domain;

        $site_title = "Liste d'articles";
        $site_url = $base_url;
        $feed_url = $base_url . "/rss.xml";
        $feed_description = "Mises à jour de la liste d'articles";

        $query = $this->fluent->from('Article')
            ->select('Article.Id, Article.Title, Article.Timestamp')
            ->select('CASE WHEN Survey.IdArticle IS NOT NULL THEN "oui" ELSE "non" END AS HasSurvey')
            ->innerJoin('Person ON Article.CreatedBy = Person.Id')
            ->leftJoin('Survey ON Article.Id = Survey.IdArticle')
            ->where('(Article.IdGroup IS NULL)')
            ->where('(Article.Published = 1)');
        if ($person = $this->getPerson([])) {
            $query = $query->whereOr('Article.IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ' . $person['Id'] . ')');
        }
        $query = $query->orderBy('Article.Timestamp DESC');
        $articles = $query->fetchAll();

        $rss_content = $this->generateRSS($articles, $site_title, $site_url, $feed_url, $feed_description);

        $rss_file_path = $_SERVER['DOCUMENT_ROOT'] . '/rss.xml';
        file_put_contents($rss_file_path, $rss_content);

        header("Refresh: 2; URL=$site_url/articles");
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
            $rss .= '<pubDate>' . date(DATE_RSS, strtotime($article['Timestamp'])) . '</pubDate>';
            $rss .= '<description>Article' . ($article['HasSurvey'] === 'oui' ? ' avec sondage' : '') . '</description>';
            $rss .= '</item>';
        }

        $rss .= '</channel>';
        $rss .= '</rss>';

        return $rss;
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
}
