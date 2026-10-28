<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleAuthorizationRow;
use app\modules\Article\valueObjects\ArticleAuthorRow;
use app\modules\Article\valueObjects\ArticleRow;
use app\modules\Article\valueObjects\ArticleSitemapRow;
use app\modules\Article\valueObjects\ArticleSummaryRow;
use app\modules\Common\valueObjects\ArticleRssRow;
use app\modules\Common\interfaces\NewsProviderInterface;

/**
 * @phpstan-import-type ArticleRowShape from ArticleRow
 */
final class ArticleDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(
        Application $application,
        private readonly AuthorizationDataHelper $authorizationDataHelper,
        private readonly ArticleAuthorizationService $authorizationService
    ) {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * @return list<ArticleSitemapRow>
     */
    public function getArticlesForAll(): array
    {
        $sql = "
            SELECT
                Id,
                LastUpdate,
                Title,
                CASE
                    WHEN Id IN (
                        SELECT CAST(Value AS INTEGER)
                        FROM Settings
                        WHERE Name = 'Home_FeaturedArticleId' AND Value != '0'
                    ) THEN 'Home_Featured'

                    WHEN Id IN (
                        SELECT CAST(Value AS INTEGER)
                        FROM Settings
                        WHERE Name = 'Home_FooterArticleId' AND Value != '0'
                    ) THEN 'Home_Footer'

                    WHEN Id IN (
                        SELECT CAST(REPLACE(Url, '/menu/show/article/', '') AS INTEGER)
                        FROM MenuItem
                        WHERE ForAnonymous = 1
                        AND Url LIKE '/menu/show/article/%'
                    ) THEN 'Menu'

                    ELSE 'Public'
                END AS ReferenceSource
            FROM Article
            WHERE
                (
                    (IdGroup IS NULL AND OnlyForMembers = 0 AND PublishedBy IS NOT NULL)
                    OR Id IN (
                        SELECT CAST(REPLACE(Url, '/menu/show/article/', '') AS INTEGER)
                        FROM MenuItem
                        WHERE ForAnonymous = 1
                        AND Url LIKE '/menu/show/article/%'
                    )
                    OR Id IN (
                        SELECT CAST(Value AS INTEGER)
                        FROM Settings
                        WHERE Name IN ('Home_FeaturedArticleId', 'Home_FooterArticleId')
                        AND Value != '0'
                    )
                )
            ORDER BY LastUpdate DESC;
        ";
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return [];
        }

        return array_values(array_map(
            ArticleSitemapRow::fromStdClass(...),
            $stmt->fetchAll(PDO::FETCH_OBJ)
        ));
    }

    /**
     * @return list<ArticleRssRow>
     */
    public function getArticlesForRss(): array
    {
        $sql = "
            SELECT
                Article.Id,
                Article.Title,
                Article.Content,
                Article.LastUpdate,
                Article.Timestamp AS CreationDate
            FROM Article
            WHERE Article.PublishedBy IS NOT NULL
            ORDER BY Article.LastUpdate DESC
            LIMIT 50
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return array_values(array_map(
            ArticleRssRow::fromStdClass(...),
            $stmt->fetchAll(PDO::FETCH_OBJ)
        ));
    }

    /**
     * @param list<int> $articleIds
     *
     * @return array<int, ArticleAuthorRow>
     */
    public function getAuthorsByArticleIds(array $articleIds): array
    {
        if (empty($articleIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
        $sql = "
            SELECT 
                Article.Id,
                CASE 
                    WHEN Individual.NickName IS NOT NULL AND Individual.NickName != '' 
                    THEN Individual.FirstName || ' ' || Individual.LastName || ' (' || Individual.NickName || ')' 
                    ELSE Individual.FirstName || ' ' || Individual.LastName 
                END AS PersonName,
                Article.Title AS ArticleTitle
            FROM Article
            JOIN Individual ON Article.CreatedBy = Individual.Id
            WHERE Article.Id IN ($placeholders)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($articleIds);

        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        $indexed = [];
        foreach ($results as $row) {
            $authorRow = ArticleAuthorRow::fromStdClass($row);
            $indexed[$authorRow->id] = $authorRow;
        }

        return $indexed;
    }

    /**
     * @param list<int> $articleIds
     *
     * @return ArticleRow|null
     */
    public function getLatestArticle(array $articleIds): ?ArticleRow
    {
        if (empty($articleIds)) {
            return null;
        }

        $placeholders = [];
        $params = [];
        foreach ($articleIds as $index => $id) {
            $key = ":id$index";
            $placeholders[] = $key;
            $params[$key] = $id;
        }

        $sql = "
            SELECT Article.*, 
                Individual.FirstName, 
                Individual.LastName, 
                Individual.NickName,
                \"Group\".Name AS GroupName,
                Article.CreatedBy
            FROM Article
            LEFT JOIN Individual ON Individual.Id = Article.CreatedBy
            LEFT JOIN \"Group\" ON Article.IdGroup = \"Group\".Id
            WHERE Article.Id IN (" . implode(',', $placeholders) . ")
            ORDER BY Article.LastUpdate DESC
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var ArticleRowShape|false $article */
        $article = $stmt->fetch(PDO::FETCH_OBJ);

        return $article ? ArticleRow::fromStdClass($article) : null;
    }

    /**
     * @return array{
     *     latestArticle: ArticleRow|null,
     *     latestArticles: list<ArticleSummaryRow>
     * }
     */
    public function getLatestArticles(?string $userEmail, int $latestArticlesCount): array
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
            'latestArticles' => $this->doGetLatestArticles($articleIds, $latestArticlesCount)
        ];
    }

    /**
     * @return list<array{
     *     type: string,
     *     id: int,
     *     title: string,
     *     date: string,
     *     url: string
     * }>
     */
    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        // The columns needed by the read rule come with the query: no extra article read per article
        $sql = "
            SELECT a.Id, a.Title, a.LastUpdate, a.CreatedBy, a.PublishedBy, a.IdGroup, a.OnlyForMembers
            FROM Article a
            WHERE a.LastUpdate >= :searchFrom
            AND a.PublishedBy IS NOT NULL
            ORDER BY a.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':searchFrom' => $searchFrom]);
        /** @var list<object{Id: int|string, Title: string, LastUpdate: string, CreatedBy: int|string, PublishedBy: int|string|null, IdGroup: int|string|null, OnlyForMembers: int|bool|null}> $articles */
        $articles = $stmt->fetchAll(PDO::FETCH_OBJ);

        $news = [];
        foreach ($articles as $article) {
            $authorization = new ArticleAuthorizationRow(
                id: (int) $article->Id,
                createdBy: (int) $article->CreatedBy,
                publishedBy: $article->PublishedBy === null ? null : (int) $article->PublishedBy,
                idGroup: $article->IdGroup === null ? null : (int) $article->IdGroup,
                onlyForMembers: (bool) $article->OnlyForMembers,
            );
            if ($this->authorizationService->canRead($authorization, $connectedUser)) {
                $news[] = [
                    'type'  => 'article',
                    'id'    => (int) $article->Id,
                    'title' => $article->Title,
                    'date'  => $article->LastUpdate,
                    'url'   => '/article/' . $article->Id,
                ];
            }
        }

        return $news;
    }

    /**
     * @param list<string> $paths
     *
     * @return array<string, bool>
     */
    public function getPathsUsedInArticles(array $paths): array
    {
        if (empty($paths)) {
            return [];
        }

        $stmt = $this->pdo->query("SELECT Content FROM Article");
        if ($stmt === false) {
            return [];
        }
        $contents = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $used = [];
        foreach ($paths as $path) {
            foreach ($contents as $content) {
                if ($content !== null && str_contains($content, $path)) {
                    $used[$path] = true;
                    break;
                }
            }
        }

        return $used;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSpotlightArticle(): ?array
    {
        $json = $this->getSetting('SpotlightArticle', '');
        if ($json === '') {
            return null;
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return ArticleRow|false
     */
    public function getWithAuthor(int $id): ArticleRow|false
    {
        $sql = "
            SELECT a.*, i.FirstName, i.LastName, i.NickName
            FROM Article a
            LEFT JOIN Individual i ON a.CreatedBy = i.Id
            WHERE a.Id = :id
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        /** @var ArticleRowShape|false $row */
        $row = $stmt->fetch(PDO::FETCH_OBJ);

        return $row ? ArticleRow::fromStdClass($row) : false;
    }

    /**
     * @return list<object>
     */
    public function inArticles(string $path): array
    {
        $sql = "
            SELECT DISTINCT a.Id, a.Title
            FROM Article a
            WHERE a.Content LIKE :path

            UNION

            SELECT DISTINCT a.Id, a.Title
            FROM Article a
            INNER JOIN Carousel c ON c.IdArticle = a.Id
            WHERE c.Item LIKE :path
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':path' => '%' . $path . '%']);

        return array_values($stmt->fetchAll(PDO::FETCH_OBJ));
    }

    public function isUserAllowedToReadArticle(string $userEmail, int $id): bool
    {
        return in_array($id, $this->getArticleIdsBasedOnAccess($userEmail), true);
    }

    public function setSpotlightArticle(int $articleId, string $spotlightUntil): void
    {
        $this->setSetting('SpotlightArticle', json_encode([
            'articleId' => $articleId,
            'spotlightUntil' => $spotlightUntil,
        ], JSON_THROW_ON_ERROR));
    }

    #region Private functions

    /**
     * @param string|null $userEmail
     *
     * @return list<int>
     */
    private function getArticleIdsBasedOnAccess(?string $userEmail): array
    {
        $noGroupArticleIds = $this->getNoGroupArticleIds();
        if (empty($userEmail)) {
            return $noGroupArticleIds;
        }

        $forMembersOnlyArticleIds = $this->getArticleIdsForMembers();
        $articleIds = empty($forMembersOnlyArticleIds)
            ? $noGroupArticleIds
            : array_merge($noGroupArticleIds, $forMembersOnlyArticleIds);

        $userGroups = $this->authorizationDataHelper->getUserGroups($userEmail);
        if (empty($userGroups)) {
            return $articleIds;
        }

        $groupArticleIds = $this->getArticleIdsByGroups($userGroups);

        return array_values(array_unique(array_merge($articleIds, $groupArticleIds)));
    }

    /**
     * @param list<int> $groupIds
     *
     * @return list<int>
     */
    private function getArticleIdsByGroups(array $groupIds): array
    {
        if (empty($groupIds)) {
            return [];
        }

        $groups = implode(',', array_fill(0, count($groupIds), '?'));
        $query = $this->pdo->prepare("
            SELECT DISTINCT Article.Id 
            FROM Article 
            WHERE Article.PublishedBy IS NOT NULL
            AND Article.IdGroup IN ($groups)
        ");
        $query->execute($groupIds);

        return array_values($query->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @return list<int>
     */
    private function getArticleIdsForMembers(): array
    {
        $query = $this->pdo->prepare("
            SELECT Article.Id 
            FROM Article 
            WHERE Article.PublishedBy IS NOT NULL 
              AND Article.IdGroup IS NULL 
              AND Article.OnlyForMembers = 1
        ");
        $query->execute();

        return array_values($query->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @param list<int> $articleIds
     *
     * @return list<ArticleSummaryRow>
     */
    private function doGetLatestArticles(array $articleIds, int $latestArticlesCount): array
    {
        if (empty($articleIds)) {
            return [];
        }

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
            LIMIT " . $latestArticlesCount;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        return array_values(array_map(ArticleSummaryRow::fromStdClass(...), $rows));
    }

    /**
     * @return list<int>
     */
    private function getNoGroupArticleIds(): array
    {
        $query = $this->pdo->prepare("
            SELECT Article.Id 
            FROM Article 
            WHERE Article.PublishedBy IS NOT NULL 
              AND Article.IdGroup IS NULL 
              AND Article.OnlyForMembers = 0
        ");
        $query->execute();

        return array_values($query->fetchAll(PDO::FETCH_COLUMN));
    }

    #endregion
}
