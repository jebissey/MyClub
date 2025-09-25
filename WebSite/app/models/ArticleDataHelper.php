<?php
declare(strict_types=1);

namespace app\models;

use PDO;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\interfaces\NewsProviderInterface;


class ArticleDataHelper extends Data implements NewsProviderInterface
{
    private const LAST_ARTICLES = 10;

    public function __construct(Application $application, private AuthorizationDataHelper $authorizationDataHelper)
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

    public function getArticleIdsBasedOnAccess(?string $userEmail): array
    {
        $noGroupArticleIds = $this->getNoGroupArticleIds();
        if (empty($userEmail)) return $noGroupArticleIds;
        $forMembersOnlyArticleIds = $this->getArticleIdsForMembers([$userEmail]);
        if (empty($forMembersOnlyArticleIds)) $articleIds = $noGroupArticleIds;
        else $articleIds = array_merge($noGroupArticleIds, $forMembersOnlyArticleIds);
        $userGroups = $this->authorizationDataHelper->getUserGroups($userEmail);
        if (empty($userGroups)) return $articleIds;
        $groupArticleIds = $this->getArticleIdsByGroups($userGroups);
        return array_unique(array_merge($articleIds, $groupArticleIds));
    }

    public function getArticlesForAll(): array
    {
        $sql = "
            SELECT Id, Title, LastUpdate
            FROM Article
            WHERE IdGroup IS NULL AND OnlyForMembers = 0
            ORDER BY LastUpdate DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getArticlesForRss(): array
    {
        $sql = "
            SELECT Article.*
            FROM Article
            WHERE Article.PublishedBy IS NOT NULL
            ORDER BY Article.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAuthor(int $articleId): object|false
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN Person.NickName != '' 
                    THEN Person.FirstName || ' ' || Person.LastName || ' (' || Person.NickName || ')' 
                    ELSE Person.FirstName || ' ' || Person.LastName 
                END AS PersonName,
                Article.Title AS ArticleTitle
            FROM Article
            JOIN Person ON Article.CreatedBy = Person.Id
            WHERE Article.Id = :articleId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':articleId' => $articleId]);
        return $stmt->fetch();
    }

    public function getLatestArticle(array $articleIds): ?object
    {
        if (empty($articleIds)) return null;
        $placeholders = [];
        $params = [];
        foreach ($articleIds as $index => $id) {
            $key = ":id$index";
            $placeholders[] = $key;
            $params[$key] = $id;
        }
        $sql = "
            SELECT Article.*, 
                Person.FirstName, 
                Person.LastName, 
                \"Group\".Name || '(' || \"Group\".Id || ')' AS GroupName
            FROM Article
            LEFT JOIN Person ON Person.Id = Article.CreatedBy
            LEFT JOIN \"Group\" ON Article.IdGroup = \"Group\".Id
            WHERE Article.Id IN (" . implode(',', $placeholders) . ")
            ORDER BY Article.LastUpdate DESC
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $article = $stmt->fetch();
        return $article ?: null;
    }

    public function getLastUpdateArticles(): ?string
    {
        $sql = "SELECT MAX(LastUpdate) AS LastMod FROM Article";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        return $result->LastMod ?? null;
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

    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $sql = "
            SELECT a.Id, a.Title, a.LastUpdate
            FROM Article a
            WHERE a.LastUpdate >= :searchFrom
            AND a.PublishedBy IS NOT NULL
            ORDER BY a.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':searchFrom' => $searchFrom]);
        $articles = $stmt->fetchAll();
        $authHelper = new AuthorizationDataHelper($this->application);
        $news = [];
        foreach ($articles as $article) {
            if ($authHelper->getArticle($article->Id, $connectedUser)) {
                $news[] = [
                    'type'  => 'article',
                    'id'    => $article->Id,
                    'title' => $article->Title,
                    'date'  => $article->LastUpdate,
                    'url'   => '/article/' . $article->Id,
                ];
            }
        }
        return $news;
    }

    public function getSpotlightArticle(): mixed
    {
        $spotlightArticleJson = $this->get('Settings', ['Name' => 'SpotlightArticle'], 'Value')->Value ?? '';
        if ($spotlightArticleJson === null) return null;
        return json_decode($spotlightArticleJson, true);
    }

    public function getWithAuthor(int $id): object|false
    {
        $sql = "
            SELECT a.*, p.FirstName, p.LastName, p.NickName
            FROM Article a
            LEFT JOIN Person p ON a.CreatedBy = p.Id
            WHERE a.Id = :id
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
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

    public function isUserAllowedToReadArticle(string $userEmail, $id)
    {
        return in_array($id, $this->getArticleIdsBasedOnAccess($userEmail));
    }

    public function setSpotlightArticle(int $articleId, string $spotlightUntil): void
    {
        $data = [
            'articleId' => $articleId,
            'spotlightUntil' => $spotlightUntil
        ];
        $this->set('Settings', ['Value' => json_encode($data)], ['Name' => 'SpotlightArticle']);
    }

    #region Private funcions
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

    private function getArticleIdsForMembers(): array
    {
        $query = $this->pdo->prepare("
            SELECT Article.Id FROM Article 
            WHERE Article.publishedBy IS NOT NULL AND Article.IdGroup IS NULL AND Article.OnlyForMembers = 1");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getLatestArticles_(array $articleIds): array
    {
        if (empty($articleIds)) return [];

        $placeholders = [];
        $params = [];
        foreach ($articleIds as $index => $id) {
            $key = ":id$index";
            $placeholders[] = $key;
            $params[$key] = $id;
        }
        $sql = "
            SELECT Id, Title, Timestamp, LastUpdate
            FROM Article
            WHERE Id IN (" . implode(',', $placeholders) . ")
            AND PublishedBy IS NOT NULL
            ORDER BY LastUpdate DESC
            LIMIT " . self::LAST_ARTICLES;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function getNoGroupArticleIds(): array
    {
        $query = $this->pdo->prepare("
            SELECT Article.Id FROM Article 
            WHERE Article.publishedBy IS NOT NULL AND Article.IdGroup IS NULL AND Article.OnlyForMembers = 0");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }
}
