<?php

namespace app\helpers;

use PDO;

class Article
{
    private $pdo;
    private $fluent;
    private $authorizations;
    private Settings $settings;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
        $this->authorizations = new Authorization($this->pdo);
        $this->settings = new Settings($this->pdo);
    }

    public function hasSurvey($id)
    {
        return $this->fluent
            ->from('Survey')
            ->join('Article ON Survey.IdArticle = Article.Id')
            ->where('IdArticle', $id)
            ->where('ClosingDate <= ?', date('now'))
            ->fetch();
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
        $spotlightArticleJson = $this->settings->get('SpotlightArticle');
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
        $this->settings->set('SpotlightArticle', json_encode($data));
    }

    public function getArticleIdsBasedOnAccess(?string $userEmail): array
    {
        $noGroupArticleIds = $this->getNoGroupArticleIds();
        if (empty($userEmail)) {
            return $noGroupArticleIds;
        }
        $forMembersOnlyArticleIds = $this->getArticleIdsForMembers([$userEmail]);
        if (empty($forMembersOnlyArticleIds)) {
            $articleIds = $noGroupArticleIds;
        } else {
            $articleIds = array_merge($noGroupArticleIds, $forMembersOnlyArticleIds);
        }
        $userGroups = $this->authorizations->getUserGroups($userEmail);
        if (empty($userGroups)) {
            return $articleIds;
        }
        $groupArticleIds = $this->getArticleIdsByGroups($userGroups);
        return array_unique(array_merge($articleIds, $groupArticleIds));
    }

    public function isUserAllowedToReadArticle(string $userEmail, $id)
    {
        return in_array($id, $this->getArticleIdsBasedOnAccess($userEmail));
    }

    public function getLatestArticle(array $articleIds): ?object
    {
        if (empty($articleIds)) {
            return null;
        }

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

    public function getArticle($id)
    {
        return $this->fluent
            ->from('Article a')
            ->leftJoin('Person p ON a.CreatedBy = p.Id')
            ->select('a.*, p.FirstName, p.LastName, p.NickName')
            ->where('a.Id', $id)
            ->fetch();
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
        if (empty($groupIds)) {
            return [];
        }
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
