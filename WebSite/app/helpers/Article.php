<?php

namespace app\helpers;

use PDO;

class Article
{
    private $pdo;
    private $fluent;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
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



    public function getAvailablePeriods()
    {
        return [
            'week' => 'Dernière semaine',
            'month' => 'Dernier mois',
            'quarter' => 'Dernier trimestre',
            'year' => 'Dernière année',
            'all' => 'Tout'
        ];
    }

    public function getDateRangeForPeriod($period)
    {
        $end = date('Y-m-d H:i:s');
        $start = '';

        switch ($period) {
            case 'week':
                $start = date('Y-m-d H:i:s', strtotime('-1 week'));
                break;
            case 'month':
                $start = date('Y-m-d H:i:s', strtotime('-1 month'));
                break;
            case 'quarter':
                $start = date('Y-m-d H:i:s', strtotime('-3 months'));
                break;
            case 'year':
                $start = date('Y-m-d H:i:s', strtotime('-1 year'));
                break;
            case 'all':
            default:
                $start = '1970-01-01 00:00:00'; // Début des temps (pratiquement)
                break;
        }

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    public function getAuthorAudienceCrosstab($startDate, $endDate)
    {
        $authors = $this->getAuthorsWithArticlesInPeriod($startDate, $endDate);
        $groups = $this->getAllGroups();
        $crosstab = [
            'authors' => $authors,
            'audiences' => [
                ['id' => 'public', 'name' => 'Tous (Public)', 'type' => 'special'],
                ['id' => 'members', 'name' => 'Club (Membres)', 'type' => 'special'],
            ],
            'data' => []
        ];

        foreach ($groups as $group) {
            $crosstab['audiences'][] = [
                'id' => $group['Id'],
                'name' => $group['Name'],
                'type' => 'group'
            ];
        }

        foreach ($crosstab['audiences'] as $audience) {
            $audienceId = $audience['id'];
            $crosstab['data'][$audienceId] = [];
        }

        foreach ($crosstab['audiences'] as $audience) {
            $audienceId = $audience['id'];
            $audienceType = $audience['type'];

            foreach ($authors as $author) {
                $authorId = $author['Id'];
                $count = 0;

                if ($audienceType === 'special') {
                    if ($audienceId === 'public') {
                        $count = $this->countArticlesByAuthorAndAudience(
                            $authorId,
                            null,
                            0,
                            $startDate,
                            $endDate
                        );
                    } elseif ($audienceId === 'members') {
                        $count = $this->countArticlesByAuthorAndAudience(
                            $authorId,
                            null,
                            1,
                            $startDate,
                            $endDate
                        );
                    }
                } else {
                    $count = $this->countArticlesByAuthorAndGroup(
                        $authorId,
                        $audienceId,
                        $startDate,
                        $endDate
                    );
                }

                $crosstab['data'][$audienceId][$authorId] = $count;
            }
        }
        return $crosstab;
    }

    private function getAuthorsWithArticlesInPeriod($startDate, $endDate)
    {
        $query = "
                SELECT DISTINCT p.Id, p.FirstName, p.LastName
                FROM Person p
                JOIN Article a ON p.Id = a.CreatedBy
                WHERE a.Timestamp BETWEEN :startDate AND :endDate
                ORDER BY p.LastName, p.FirstName
            ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':startDate' => $startDate,
            ':endDate' => $endDate
        ]);
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($authors)) {
            $query = "
                    SELECT DISTINCT p.Id, p.FirstName, p.LastName
                    FROM Person p
                    JOIN Article a ON p.Id = a.CreatedBy
                    ORDER BY p.LastName, p.FirstName
                    LIMIT 5
                ";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $authors;
    }

    private function getAllGroups()
    {
        $query = "SELECT Id, Name FROM `Group` WHERE Inactivated = 0 ORDER BY Name";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function countArticlesByAuthorAndAudience($authorId, $groupId, $onlyForMembers, $startDate, $endDate)
    {
        $query = "
                SELECT COUNT(*) as total
                FROM Article
                WHERE CreatedBy = :authorId
                AND Timestamp BETWEEN :startDate AND :endDate
            ";
        $params = [
            ':authorId' => $authorId,
            ':startDate' => $startDate,
            ':endDate' => $endDate];

        if ($groupId === null) {
            $query .= " AND IdGroup IS NULL";
        } else {
            $query .= " AND IdGroup = :groupId";
            $params[':groupId'] = $groupId;
        }

        $query .= " AND OnlyForMembers = :onlyForMembers";
        $params[':onlyForMembers'] = $onlyForMembers;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    private function countArticlesByAuthorAndGroup($authorId, $groupId, $startDate, $endDate)
    {
        $query = "
                SELECT COUNT(*) as total
                FROM Article
                WHERE CreatedBy = :authorId
                AND IdGroup = :groupId
                AND Timestamp BETWEEN :startDate AND :endDate";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':authorId' => $authorId,
            ':groupId' => $groupId,
            ':startDate' => $startDate,
            ':endDate' => $endDate
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    public function calculateTotals($crosstabData)
    {
        $totals = [
            'byAuthor' => [],
            'byAudience' => []
        ];

        foreach ($crosstabData['authors'] as $author) {
            $authorId = $author['Id'];
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
}