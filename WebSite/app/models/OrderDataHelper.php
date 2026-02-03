<?php

declare(strict_types=1);

namespace app\models;

use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\interfaces\NewsProviderInterface;

class OrderDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(Application $application, private ArticleDataHelper $articleDataHelper)
    {
        parent::__construct($application);
    }

    public function articleHasOrderNotClosed(int $articleId): object|bool
    {
        $sql = "
            SELECT Order.*
            FROM Order
            JOIN Article ON Order.IdArticle = Article.Id
            WHERE Order.IdArticle = :articleId
            AND Order.ClosingDate >= datetime('now')
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':articleId' => $articleId]);
        $order = $stmt->fetch();
        return $order;
    }

    public function getWithCreator(int $articleId): object|bool
    {
        $article = $this->get('Article', ['Id' => $articleId], 'Id');
        if ($article === false) throw new QueryException("Article {$articleId} doesn't exist");

        $sql = "
            SELECT o.*, a.CreatedBy
            FROM \"Order\" o
            INNER JOIN Article a ON o.IdArticle = a.Id
            WHERE o.IdArticle = :articleId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':articleId' => $article->Id]);
        return $stmt->fetch();
    }

    public function getPendingOrderResponses(): array
    {
        $query = "
        SELECT 
            p.Id AS PersonId, 
            p.Email, 
            a.Id AS ArticleId, 
            a.Title AS ArticleTitle, 
            o.Id AS OrderId, 
            o.Question AS OrderQuestion, 
            o.ClosingDate
        FROM Person p
        CROSS JOIN Order o
        JOIN Article a ON o.IdArticle = a.Id
        LEFT JOIN OrderReply r ON r.IdOrder = o.Id AND r.IdPerson = p.Id
        LEFT JOIN PersonGroup pg ON pg.IdPerson = p.Id AND pg.IdGroup = a.IdGroup
        WHERE 
            a.PublishedBy IS NOT NULL
            AND p.Inactivated = 0
            AND o.ClosingDate > date('now')
            AND (
                a.IdGroup IS NULL
                OR pg.IdGroup IS NOT NULL 
            )
            AND r.Id IS NULL
        ORDER BY o.ClosingDate, p.LastName, p.FirstName";
        return $this->pdo->query($query)->fetchAll();
    }

    public function getNews(ConnectedUser $connectedUser, $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) return $news;
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
            JOIN Order o ON o.Id = r.IdOrder
            JOIN Article a ON a.Id = o.IdArticle
            JOIN Person p ON p.Id = a.CreatedBy
            JOIN Person v ON v.Id = r.IdPerson
            WHERE r.LastUpdate >= :searchFrom
            GROUP BY o.Id
            ORDER BY LastActivity DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':searchFrom' => $searchFrom]);
        $orders = $stmt->fetchAll();
        $news = [];
        $authorizationDataHelper = new AuthorizationDataHelper($this->application);
        foreach ($orders as $order) {
            if (
                $authorizationDataHelper->getArticle($order->IdArticle, $connectedUser)
                && $authorizationDataHelper->canPersonReadOrderResults($this->articleDataHelper->getWithAuthor($order->IdArticle), $connectedUser)
            ) {
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
        }
        return $news;
    }
}