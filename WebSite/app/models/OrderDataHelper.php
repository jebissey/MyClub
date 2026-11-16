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
 * @phpstan-import-type OrderWithCreatorRowShape from OrderWithCreatorRow
 * @phpstan-import-type OrderNewsRowShape from OrderNewsRow
 * @phpstan-import-type ClosingVisibilityRowShape from ClosingVisibilityRow
 * @phpstan-import-type NewsFeedItem from NewsProviderInterface
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
        /** @var OrderWithCreatorRowShape|false $result */
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        if ($result === false) {
            return false;
        }
        return OrderWithCreatorRow::fromStdClass($result);
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
            $articleRow = $this->articleDataHelper->getWithAuthor($order->idArticle);
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
            $closingRow = $this->get('Order', ['IdArticle' => $order->idArticle], 'Id, ClosingDate, Visibility');
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
                'id'     => $order->idArticle,
                'title'  => $order->question,
                'detail' => 'Commandes : ' . $order->orderers,
                'from'   => $order->firstName . ' ' . $order->lastName,
                'date'   => $order->lastActivity,
                'url'    => '/order/results/' . $order->idArticle
            ];
        }
        return $news;
    }
}
