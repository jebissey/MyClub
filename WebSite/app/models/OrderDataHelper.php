<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\modules\Common\interfaces\NewsProviderInterface;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleAuthorizationRow;
use app\modules\Article\valueObjects\OrderWithCreatorRow;
use app\modules\Article\valueObjects\OrderNewsRow;
use app\modules\Common\valueObjects\ClosingVisibilityRow;

/**
 * @phpstan-type PendingOrderResponse object{
 *     PersonId: int,
 *     Email: string,
 *     ArticleId: int,
 *     ArticleTitle: string,
 *     OrderId: int,
 *     OrderQuestion: string,
 *     ClosingDate: string
 * }
 * @phpstan-type NewsItem array{
 *     type: string,
 *     id: int,
 *     title: string,
 *     detail: string,
 *     from: string,
 *     date: string,
 *     url: string
 * }
 * @phpstan-import-type OrderNewsRowShape from OrderNewsRow
 * @phpstan-import-type ClosingVisibilityRowShape from ClosingVisibilityRow
 */
final class OrderDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(
        Application $application,
        private readonly ArticleDataHelper $articleDataHelper,
        private readonly ArticleAuthorizationService $authorizationService
    ) {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    public function articleHasOrderNotClosed(int $articleId): object|bool
    {
        $sql = "
            SELECT \"Order\".*
            FROM \"Order\"
            JOIN Article ON \"Order\".IdArticle = Article.Id
            WHERE \"Order\".IdArticle = :articleId
            AND \"Order\".ClosingDate >= datetime('now')
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':articleId' => $articleId]);
        $order = $stmt->fetch(PDO::FETCH_OBJ);
        return is_object($order) ? $order : false;
    }

    public function getWithCreator(int $articleId): OrderWithCreatorRow|false
    {
        $article = $this->get('Article', ['Id' => $articleId], 'Id');
        if ($article === false) {
            throw new QueryException("Article {$articleId} doesn't exist");
        }

        $sql = "
            SELECT o.Id, o.Question, o.Options, o.IdArticle, o.ClosingDate, o.Visibility, a.CreatedBy
            FROM \"Order\" o
            INNER JOIN Article a ON o.IdArticle = a.Id
            WHERE o.IdArticle = :articleId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':articleId' => $articleId]);
        /** @var object{Id: int|string, Question: string, Options: string, IdArticle: int|string, ClosingDate: string, Visibility: string, CreatedBy: int|string}|false $result */
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        if ($result === false) {
            return false;
        }
        return OrderWithCreatorRow::fromStdClass($result);
    }

    /**
     * @return array<int, PendingOrderResponse>
     */
    public function getPendingOrderResponses(): array
    {
        $query = "
        SELECT 
            i.Id AS PersonId, 
            i.Email, 
            a.Id AS ArticleId, 
            a.Title AS ArticleTitle, 
            o.Id AS OrderId, 
            o.Question AS OrderQuestion, 
            o.ClosingDate
        FROM Individual i
        INNER JOIN Member m ON m.Id = i.Id
        CROSS JOIN \"Order\" o
        JOIN Article a ON o.IdArticle = a.Id
        LEFT JOIN OrderReply r ON r.IdOrder = o.Id AND r.IdPerson = i.Id
        LEFT JOIN MemberGroup mg ON mg.IdMember = i.Id AND mg.IdGroup = a.IdGroup
        WHERE 
            a.PublishedBy IS NOT NULL
            AND m.Inactivated = 0
            AND o.ClosingDate > date('now')
            AND (
                a.IdGroup IS NULL
                OR mg.IdGroup IS NOT NULL 
            )
            AND r.Id IS NULL
        ORDER BY o.ClosingDate, i.LastName, i.FirstName";
        $stmt = $this->pdo->query($query);
        if ($stmt === false) {
            throw new QueryException("Failed to execute query in getPendingOrderResponses");
        }
        /** @var array<int, PendingOrderResponse> $result */
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $result;
    }

    /**
     * @return array<int, NewsItem>
     */
    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) {
            return $news;
        }
        $sql = "
            SELECT 
                p.FirstName,
                p.LastName,
                o.Question,
                o.ClosingDate,
                o.Visibility,
                o.IdArticle,
                MAX(r.LastUpdate) AS LastActivity,
                GROUP_CONCAT(
                    v.FirstName || ' ' || v.LastName || ' (' ||
                    strftime('%d/%m/%Y', r.LastUpdate) || ')',
                    ', '
                ) AS Orderers
            FROM OrderReply r
            JOIN \"Order\" o ON o.Id = r.IdOrder
            JOIN Article a ON a.Id = o.IdArticle
            JOIN Individual p ON p.Id = a.CreatedBy
            JOIN Individual v ON v.Id = r.IdPerson
            WHERE r.LastUpdate >= :searchFrom
            GROUP BY o.Id
            ORDER BY LastActivity DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':searchFrom' => $searchFrom]);
        /** @var list<OrderNewsRowShape> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $orders = array_map(
            static fn(object $row): OrderNewsRow => OrderNewsRow::fromStdClass($row),
            $rows
        );

        foreach ($orders as $order) {
            $articleRow = $this->articleDataHelper->getWithAuthor($order->IdArticle);
            if ($articleRow === false) {
                continue;
            }
            $article = ArticleAuthorizationRow::fromArticleRow($articleRow);

            // 1st gate: the article must be readable
            if (!$this->authorizationService->canRead($article, $connectedUser)) {
                continue;
            }

            // 2nd gate: the visibility chosen by the creator of the order
            /** @var ClosingVisibilityRowShape|false $closingRow */
            $closingRow = $this->get('Order', ['IdArticle' => $order->IdArticle], 'Id, ClosingDate, Visibility');
            if (
                $closingRow === false
                || !$this->authorizationService->canReadOrderResults(
                    ClosingVisibilityRow::fromStdClass($closingRow),
                    $article,
                    $connectedUser
                )
            ) {
                continue;
            }

            $news[] = [
                'type'   => 'order',
                'id'     => $order->IdArticle,
                'title'  => $order->Question,
                'detail' => 'Commandes : ' . $order->Orderers,
                'from'   => $order->FirstName . ' ' . $order->LastName,
                'date'   => $order->LastActivity,
                'url'    => '/order/results/' . $order->IdArticle
            ];
        }
        return $news;
    }
}
