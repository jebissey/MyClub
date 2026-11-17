<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\helpers\Application;
use app\helpers\To;

/**
 * Read-only message counters.
 */
final class MessageStatisticsDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    public function countUserMessagesForArticle(int $articleId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Message WHERE "From" = :from AND ArticleId = :articleId');
        $stmt->execute([':from' => 'User', ':articleId' => $articleId]);

        return To::requireInt($stmt->fetchColumn());
    }

    /**
     * @param array{start: string, end: string} $season
     * @return array<string, int> message count indexed by lowercase email
     */
    public function countByEmailForSeason(array $season): array
    {
        $query = $this->pdo->prepare("
            SELECT LOWER(i.Email) AS Email, COUNT(m.Id) AS MessageCount
            FROM Message m
            JOIN Member     mb ON mb.Id = m.PersonId
            JOIN Individual i  ON i.Id  = mb.Id
            WHERE m.LastUpdate BETWEEN :start AND :end
            GROUP BY i.Email
        ");
        $query->execute([
            ':start' => $season['start'],
            ':end'   => $season['end'],
        ]);

        /** @var array<string, int> $result */
        $result = $query->fetchAll(PDO::FETCH_KEY_PAIR);

        return $result;
    }
}
