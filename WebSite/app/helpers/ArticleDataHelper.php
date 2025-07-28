<?php

namespace app\helpers;

use PDO;

class ArticleDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function calculateTotals($crosstabData)
    {
        $totals = [
            'byAuthor' => [],
            'byAudience' => []
        ];

        foreach ($crosstabData['authors'] as $author) {
            $authorId = $author->Id;
            $total = 0;

            foreach ($crosstabData['audiences'] as $audience) {
                $audienceId = $audience['id'];
                if (isset($crosstabData['data'][$audienceId][$authorId])) {
                    $total += $crosstabData['data'][$audienceId][$authorId];
                }
            }

            $totals['byAuthor'][$authorId] = $total;
        }

        foreach ($crosstabData['audiences'] as $audience) {
            $audienceId = $audience['id'];
            $total = 0;

            if (isset($crosstabData['data'][$audienceId])) {
                foreach ($crosstabData['data'][$audienceId] as $count) {
                    $total += $count;
                }
            }

            $totals['byAudience'][$audienceId] = $total;
        }

        return $totals;
    }

    public function delete_(int $id): void
    {
        $this->fluent->deleteFrom('Article')->where('Id', $id)->execute();
    }

    public function insert($personId)
    {
        return $this->fluent->insertInto('Article', [
            'Title'     => '',
            'Content'   => '',
            'CreatedBy' => $personId
        ])->execute();
    }

    public function update($id, $personId, $published, $title = '', $content = '', $idGroup = null, $membersOnly = 1)
    {
        $this->fluent->update('Article')
            ->set([
                'Title'          => $title,
                'Content'        => $content,
                'PublishedBy'    => ($published == 1 ? $personId : null),
                'IdGroup'        => $idGroup,
                'OnlyForMembers' => $membersOnly,
                'LastUpdate'     => date('Y-m-d H:i:s')
            ])
            ->where('Id', $id)
            ->execute();
    }

    public function isSpotlightActive(): bool
    {
        $spotlight = $this->getSpotlightArticle();
        if ($spotlight === null) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $spotlightUntil = $spotlight['spotlightUntil'];
        return strtotime($now) < strtotime($spotlightUntil);
    }

    public function getSpotlightArticle()
    {
        $spotlightArticleJson = (new SettingsDataHelper($this->application))->get_('SpotlightArticle');
        if ($spotlightArticleJson === null) {
            return null;
        }
        return json_decode($spotlightArticleJson, true);
    }

    public function setSpotlightArticle($articleId, $spotlightUntil)
    {
        $data = [
            'articleId' => $articleId,
            'spotlightUntil' => $spotlightUntil
        ];
        (new SettingsDataHelper($this->application))->set_('SpotlightArticle', json_encode($data));
    }

    public function getArticleIdsBasedOnAccess(?string $userEmail): array
    {
        $noGroupArticleIds = $this->getNoGroupArticleIds();
        if (empty($userEmail)) return $noGroupArticleIds;
        $forMembersOnlyArticleIds = $this->getArticleIdsForMembers([$userEmail]);
        if (empty($forMembersOnlyArticleIds)) $articleIds = $noGroupArticleIds;
        else $articleIds = array_merge($noGroupArticleIds, $forMembersOnlyArticleIds);
        $userGroups = (new AuthorizationDataHelper($this->application))->getUserGroups($userEmail);
        if (empty($userGroups)) return $articleIds;
        $groupArticleIds = $this->getArticleIdsByGroups($userGroups);
        return array_unique(array_merge($articleIds, $groupArticleIds));
    }

    public function isUserAllowedToReadArticle(string $userEmail, $id)
    {
        return in_array($id, $this->getArticleIdsBasedOnAccess($userEmail));
    }

    public function getLatestArticle(array $articleIds): ?object
    {
        if (empty($articleIds)) return null;
        $article = $this->fluent->from('Article')
            ->select('Article.*, Person.FirstName, Person.LastName, "Group".Name || \'(\' || "Group".Id || \')\' AS GroupName')
            ->leftJoin('Person ON Person.Id = Article.CreatedBy')
            ->leftJoin('"Group" ON Article.IdGroup = "Group".Id')
            ->where('Article.Id', $articleIds)
            ->orderBy('Article.LastUpdate DESC')
            ->limit(1)
            ->fetch();
        return $article ?: null;
    }

    public function getLatestArticles(?string $userEmail = null): array
    {
        $articleIds = $this->getArticleIdsBasedOnAccess($userEmail);
        if (empty($articleIds)) {
            return [
                'latestArticle' => null,
                'latestArticles' => []
            ];
        }
        return [
            'latestArticle' => $this->getLatestArticle($articleIds),
            'latestArticles' => $this->getLatestArticles_($articleIds)
        ];
    }

    public function getWithAuthor($id)
    {
        return $this->fluent
            ->from('Article a')
            ->leftJoin('Person p ON a.CreatedBy = p.Id')
            ->select('a.*, p.FirstName, p.LastName, p.NickName')
            ->where('a.Id', $id)
            ->fetch();
    }

    public function getAuthor($articleId)
    {
        return $this->fluent
            ->from('Article')
            ->where('Article.Id = ?', $articleId)
            ->join('Person ON Article.CreatedBy = Person.Id')
            ->select('CASE WHEN Person.NickName != "" THEN Person.FirstName || " " || Person.LastName || " (" || Person.NickName || ")" ELSE Person.FirstName || " " || Person.LastName END AS PersonName')
            ->select('Article.Title AS ArticleTitle')
            ->fetch();
    }

    public function getArticlesForRss($personId)
    {
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
        return $query->fetchAll();
    }

    public function getLastUpdateArticles()
    {
        return $this->fluent->from('Article')
            ->select(null)
            ->select('MAX(LastUpdate) AS LastMod')
            ->fetch('LastMod');
    }

    public function getArticlesForAll()
    {
        return $this->fluent->from('Article')
            ->select('Id, Title, LastUpdate')
            ->where('IdGroup IS NULL AND OnlyForMembers = 0')
            ->orderBy('LastUpdate DESC')
            ->fetchAll();
    }

    public function getArticleNews($person, $searchFrom)
    {
        $articles = $this->fluent->from('Article a')
            ->select('a.Id, a.Title, a.LastUpdate')
            ->where('a.LastUpdate >= ?', $searchFrom)
            ->where('a.PublishedBy IS NOT NULL')
            ->orderBy('a.LastUpdate DESC')
            ->fetchAll();
        $news = [];
        foreach ($articles as $article) {
            if ((new AuthorizationDataHelper($this->application))->getArticle($article->Id, $person)) {
                $news[] = [
                    'type'  => 'article',
                    'id'    => $article->Id,
                    'title' => $article->Title,
                    'date'  => $article->LastUpdate,
                    'url'   => '/articles/' . $article->Id
                ];
            }
        }
        return $news;
    }

    #region Private funcions
    private function getNoGroupArticleIds(): array
    {
        $query = $this->pdo->prepare("
            SELECT Article.Id FROM Article 
            WHERE Article.publishedBy IS NOT NULL AND Article.IdGroup IS NULL AND Article.OnlyForMembers = 0");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getArticleIdsForMembers(): array
    {
        $query = $this->pdo->prepare("
            SELECT Article.Id FROM Article 
            WHERE Article.publishedBy IS NOT NULL AND Article.IdGroup IS NULL AND Article.OnlyForMembers = 1");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getArticleIdsByGroups(array $groupIds): array
    {
        if (empty($groupIds)) return [];
        $groups = implode(',', array_fill(0, count($groupIds), '?'));
        $query = $this->pdo->prepare("
            SELECT DISTINCT Article.Id FROM Article 
            WHERE Article.publishedBy IS NOT NULL
            AND Article.IdGroup IN ($groups)");
        $query->execute($groupIds);
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getLatestArticles_(array $articleIds): array
    {
        if (empty($articleIds)) {
            return [];
        }

        return $this->fluent->from('Article')
            ->select(null)
            ->select('Id, Title, Timestamp, LastUpdate')
            ->where('Id', $articleIds)
            ->where('publishedBy IS NOT NULL')
            ->orderBy('LastUpdate DESC')
            ->limit(10)
            ->fetchAll() ?: [];
    }
}
