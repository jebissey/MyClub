<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\helpers\Application;

class PersonStatisticsDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getStats(object $person, string $seasonStart, string $seasonEnd)
    {
        $stats = [
            'person' => $person,
            'seasonStart' => $seasonStart,
            'seasonEnd' => $seasonEnd,
            'articles' => $this->getArticleStats($person->Id, $seasonStart, $seasonEnd),
            'surveys' => $this->getSurveyStats($person->Id, $seasonStart, $seasonEnd),
            'surveyReplies' => $this->getSurveyRepliesStats($person->Id, $seasonStart, $seasonEnd),
            'designs' => $this->getDesignStats($person->Id, $seasonStart, $seasonEnd),
            'designVotes' => $this->getDesignVoteStats($person->Id, $seasonStart, $seasonEnd),
            'events' => $this->getEventStats($person->Id, $seasonStart, $seasonEnd),
            'eventParticipations' => $this->getEventParticipationStats($person->Id, $seasonStart, $seasonEnd),
            'participantSupplies' => $this->getParticipantSupplyStats($person->Id, $seasonStart, $seasonEnd),
            'participantMessages' => $this->getParticipantMessageStats($person->Id, $seasonStart, $seasonEnd),
        ];

        return $stats;
    }

    public function getAvailableSeasons()
    {
        $query = "
            SELECT MIN(LastUpdate) as min_date FROM (
                SELECT MIN(LastUpdate) as LastUpdate FROM Article
                UNION
                SELECT MIN(StartTime) as LastUpdate FROM Event
            )
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $firstDate = $stmt->fetch(PDO::FETCH_OBJ)->min_date;

        if (!$firstDate) {
            $firstDate = date('Y-m-d');
        }

        $firstYear = date('Y', strtotime($firstDate));
        $currentYear = date('Y');

        $seasons = [];

        for ($year = $firstYear; $year <= $currentYear + 1; $year++) {
            $seasonStart = ($year - 1) . '-09-01';
            $seasonEnd = $year . '-08-31';

            $seasons[] = [
                'label' => 'Saison ' . ($year - 1) . '-' . $year,
                'start' => $seasonStart,
                'end' => $seasonEnd
            ];
        }

        return $seasons;
    }

    #region Private functions
    private function getArticleCount(?int $personId, string $seasonStart, string $seasonEnd): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM Article
            WHERE LastUpdate BETWEEN :seasonStart AND :seasonEnd
        ";

        $params = [
            ':seasonStart' => $seasonStart,
            ':seasonEnd'   => $seasonEnd,
        ];

        if ($personId !== null) {
            $sql .= ' AND CreatedBy = :personId';
            $params[':personId'] = $personId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function getArticleStats(int $personId, string $seasonStart, string $seasonEnd): array
    {
        $userArticlesCount = $this->getArticleCount($personId, $seasonStart, $seasonEnd);
        $totalArticlesCount = $this->getArticleCount(null, $seasonStart, $seasonEnd);

        return [
            'user'       => $userArticlesCount,
            'total'      => $totalArticlesCount,
            'percentage' => $totalArticlesCount > 0
                ? round(($userArticlesCount / $totalArticlesCount) * 100, 2)
                : 0
        ];
    }

    private function getSurveyStats(int $personId, string $seasonStart, string $seasonEnd): array
    {
        $query = "
            SELECT COUNT(s.Id) as count 
            FROM Survey s
            JOIN Article a ON s.IdArticle = a.Id
            WHERE a.CreatedBy = ? 
            AND s.Id IN (
                SELECT Id FROM Survey WHERE IdArticle IN (
                    SELECT Id FROM Article WHERE LastUpdate BETWEEN ? AND ?
                )
            )
        ";
        $userSurveys = $this->pdo->prepare($query);
        $userSurveys->execute([$personId, $seasonStart, $seasonEnd]);
        $userSurveysCount = $userSurveys->fetch(PDO::FETCH_OBJ)->count;

        $query = "
            SELECT COUNT(*) as count 
            FROM Survey 
            WHERE IdArticle IN (
                SELECT Id FROM Article WHERE LastUpdate BETWEEN ? AND ?
            )
        ";
        $totalSurveys = $this->pdo->prepare($query);
        $totalSurveys->execute([$seasonStart, $seasonEnd]);
        $totalSurveysCount = $totalSurveys->fetch(PDO::FETCH_OBJ)->count;

        return [
            'user' => $userSurveysCount,
            'total' => $totalSurveysCount,
            'percentage' => $totalSurveysCount > 0 ? round(($userSurveysCount / $totalSurveysCount) * 100, 2) : 0
        ];
    }

    private function getSurveyRepliesStats(int $personId, string $seasonStart, string $seasonEnd): array
    {
        $query = "
            SELECT COUNT(*) as count 
            FROM Reply 
            WHERE IdPerson = ? 
            AND Id IN (
                SELECT r.Id FROM Reply r
                JOIN Survey s ON r.IdSurvey = s.Id
                JOIN Article a ON s.IdArticle = a.Id
                WHERE a.LastUpdate BETWEEN ? AND ?
            )";
        $userReplies = $this->pdo->prepare($query);
        $userReplies->execute([$personId, $seasonStart, $seasonEnd]);
        $userRepliesCount = $userReplies->fetch(PDO::FETCH_OBJ)->count;

        $query = "
            SELECT COUNT(*) as count 
            FROM Reply 
            WHERE Id IN (
                SELECT r.Id FROM Reply r
                JOIN Survey s ON r.IdSurvey = s.Id
                JOIN Article a ON s.IdArticle = a.Id
                WHERE a.LastUpdate BETWEEN ? AND ?
            )";
        $totalReplies = $this->pdo->prepare($query);
        $totalReplies->execute([$seasonStart, $seasonEnd]);
        $totalRepliesCount = $totalReplies->fetch(PDO::FETCH_OBJ)->count;

        return [
            'user'       => $userRepliesCount,
            'total'      => $totalRepliesCount,
            'percentage' => $totalRepliesCount > 0
                ? round(($userRepliesCount / $totalRepliesCount) * 100, 2)
                : 0
        ];
    }

    private function getDesignCount(?int $personId, string $seasonStart, string $seasonEnd): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM Design
            WHERE datetime(LastUpdate) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)
        ";

        $params = [
            ':seasonStart' => $seasonStart,
            ':seasonEnd'   => $seasonEnd,
        ];

        if ($personId !== null) {
            $sql .= ' AND IdPerson = :personId';
            $params[':personId'] = $personId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function getDesignStats(int $personId, string $seasonStart, string $seasonEnd): array
    {
        $userDesignsCount = $this->getDesignCount($personId, $seasonStart, $seasonEnd);
        $totalDesignsCount = $this->getDesignCount(null, $seasonStart, $seasonEnd);

        return [
            'user'       => $userDesignsCount,
            'total'      => $totalDesignsCount,
            'percentage' => $totalDesignsCount > 0
                ? round(($userDesignsCount / $totalDesignsCount) * 100, 2)
                : 0
        ];
    }

    private function getDesignVoteCount(?int $personId, string $seasonStart, string $seasonEnd): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM DesignVote dv
            INNER JOIN Design d ON dv.IdDesign = d.Id
            WHERE datetime(d.LastUpdate) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)
        ";

        $params = [
            ':seasonStart' => $seasonStart,
            ':seasonEnd'   => $seasonEnd,
        ];

        if ($personId !== null) {
            $sql .= ' AND dv.IdPerson = :personId';
            $params[':personId'] = $personId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function getDesignVoteStats(int $personId, string $seasonStart, string $seasonEnd): array
    {
        $userVotesCount = $this->getDesignVoteCount($personId, $seasonStart, $seasonEnd);
        $totalVotesCount = $this->getDesignVoteCount(null, $seasonStart, $seasonEnd);

        return [
            'user'       => $userVotesCount,
            'total'      => $totalVotesCount,
            'percentage' => $totalVotesCount > 0
                ? round(($userVotesCount / $totalVotesCount) * 100, 2)
                : 0.0,
        ];
    }

    private function getEventStats(int $personId, string $seasonStart, string $seasonEnd): array
    {
        $stats = [];
        $stmt = $this->pdo->prepare("
            SELECT 
                et.Id,
                et.Name,
                COUNT(e.Id) AS total,
                SUM(CASE WHEN e.CreatedBy = ? THEN 1 ELSE 0 END) AS user
            FROM EventType et
            LEFT JOIN Event e ON e.IdEventType = et.Id 
                AND datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
            GROUP BY et.Id, et.Name
        ");
        $stmt->execute([$personId, $seasonStart, $seasonEnd]);
        $eventTypes = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($eventTypes as $eventType) {
            $userCount = (int) $eventType->user;
            $totalCount = (int) $eventType->total;

            $stats[$eventType->Id] = [
                'typeName'   => $eventType->Name,
                'user'       => $userCount,
                'total'      => $totalCount,
                'percentage' => $totalCount > 0
                    ? round(($userCount / $totalCount) * 100, 2)
                    : 0
            ];
        }

        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN CreatedBy = ? THEN 1 ELSE 0 END) AS user
            FROM Event 
            WHERE datetime(StartTime) BETWEEN datetime(?) AND datetime(?)
        ");
        $stmt->execute([$personId, $seasonStart, $seasonEnd]);
        $totals = $stmt->fetch(PDO::FETCH_OBJ);

        $userAllEventsCount = (int) $totals->user;
        $totalAllEventsCount = (int) $totals->total;

        $stats['total'] = [
            'typeName'   => 'Total',
            'user'       => $userAllEventsCount,
            'total'      => $totalAllEventsCount,
            'percentage' => $totalAllEventsCount > 0
                ? round(($userAllEventsCount / $totalAllEventsCount) * 100, 2)
                : 0
        ];

        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN g.InvitedBy = ? THEN 1 ELSE 0 END) AS user
            FROM Guest g
            INNER JOIN Event e ON e.Id = g.IdEvent
            WHERE datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
        ");
        $stmt->execute([$personId, $seasonStart, $seasonEnd]);
        $invitations = $stmt->fetch(PDO::FETCH_OBJ);

        $userInvitationCount = (int) $invitations->user;
        $totalInvitationCount = (int) $invitations->total;

        if ($userInvitationCount > 0) {
            $stats['invitation'] = [
                'typeName'   => 'Invitations envoyées',
                'user'       => $userInvitationCount,
                'total'      => $totalInvitationCount,
                'percentage' => $totalInvitationCount > 0
                    ? round(($userInvitationCount / $totalInvitationCount) * 100, 2)
                    : 0
            ];
        }
        return $stats;
    }

    private function getEventParticipationStats(int $personId, string $seasonStart, string $seasonEnd): array
    {
        $stats = [];

        $stmt = $this->pdo->query('SELECT * FROM EventType');
        $eventTypes = $stmt->fetchAll(PDO::FETCH_OBJ);

        $sql = "SELECT 
                e.IdEventType,
                et.Name as typeName,
                COUNT(CASE WHEN p.IdPerson = ? THEN 1 END) as user_count,
                COUNT(*) as total_users_count,
                COUNT(DISTINCT e.Id) as event_count
            FROM Participant p
            INNER JOIN Event e ON p.IdEvent = e.Id
            INNER JOIN EventType et ON e.IdEventType = et.Id
            WHERE datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
            GROUP BY e.IdEventType, et.Name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId, $seasonStart, $seasonEnd]);
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);
        foreach ($results as $result) {
            $stats[$result->IdEventType] = [
                'typeName'   => $result->typeName,
                'events'     => (int)$result->event_count,
                'user'       => (int)$result->user_count,
                'total'      => (int)$result->total_users_count,
                'percentage' => $result->total_users_count > 0
                    ? round(($result->user_count / $result->total_users_count) * 100, 2)
                    : 0
            ];
        }
        foreach ($eventTypes as $eventType) {
            if (!isset($stats[$eventType->Id])) {
                $stats[$eventType->Id] = [
                    'typeName'   => $eventType->Name,
                    'events'     => 0,
                    'user'       => 0,
                    'total'      => 0,
                    'percentage' => 0
                ];
            }
        }
        $totalEvents = array_sum(array_column($stats, 'events'));
        $totalUser = array_sum(array_column($stats, 'user'));
        $totalParticipation = array_sum(array_column($stats, 'total'));
        $stats['total'] = [
            'typeName'   => 'Total',
            'events'     => $totalEvents,
            'user'       => $totalUser,
            'total'      => $totalParticipation,
            'percentage' => $totalParticipation > 0
                ? round(($totalUser / $totalParticipation) * 100, 2)
                : 0
        ];

        return $stats;
    }

    private function getParticipantSupplyCount(?int $personId, string $seasonStart, string $seasonEnd): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM ParticipantSupply ps
            INNER JOIN Participant p ON ps.IdParticipant = p.Id
            INNER JOIN Event e ON p.IdEvent = e.Id
            WHERE datetime(e.StartTime) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)
        ";

        $params = [
            ':seasonStart' => $seasonStart,
            ':seasonEnd'   => $seasonEnd,
        ];

        if ($personId !== null) {
            $sql .= ' AND p.IdPerson = :personId';
            $params[':personId'] = $personId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function getParticipantSupplyStats(int $personId, string $seasonStart, string $seasonEnd): array
    {
        $userSuppliesCount = $this->getParticipantSupplyCount($personId, $seasonStart, $seasonEnd);
        $totalSuppliesCount = $this->getParticipantSupplyCount(null, $seasonStart, $seasonEnd);

        return [
            'user'       => $userSuppliesCount,
            'total'      => $totalSuppliesCount,
            'percentage' => $totalSuppliesCount > 0
                ? round(($userSuppliesCount / $totalSuppliesCount) * 100, 2)
                : 0
        ];
    }

    private function getParticipantMessageCount(?int $personId, string $seasonStart, string $seasonEnd, string $from): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM Message m
            INNER JOIN Event e ON m.EventId = e.Id
            WHERE datetime(e.StartTime) BETWEEN datetime(:seasonStart) AND datetime(:seasonEnd)
            AND \"From\" = :from
        ";

        $params = [
            ':seasonStart' => $seasonStart,
            ':seasonEnd'   => $seasonEnd,
            ':from'        => $from,
        ];

        if ($personId !== null) {
            $sql .= ' AND m.PersonId = :personId';
            $params[':personId'] = $personId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function getParticipantMessageStats(int $personId, string $seasonStart, string $seasonEnd): array
    {
        $userMessagesCount = $this->getParticipantMessageCount($personId, $seasonStart, $seasonEnd, 'User');
        $totalUsersMessagesCount = $this->getParticipantMessageCount(null, $seasonStart, $seasonEnd, 'User');
        $webappMessagesCount = $this->getParticipantMessageCount($personId, $seasonStart, $seasonEnd, 'Webapp');
        $totalWebappMessagesCount = $this->getParticipantMessageCount(null, $seasonStart, $seasonEnd, 'Webapp');

        return [
            'user'       => $userMessagesCount,
            'totalUsers'      => $totalUsersMessagesCount,
            'percentage' => $totalUsersMessagesCount > 0
                ? round(($userMessagesCount / $totalUsersMessagesCount) * 100, 2)
                : 0,
            'webapp'       => $webappMessagesCount,
            'totalWebapp'      => $totalWebappMessagesCount,
            'percentageWebapp' => $totalWebappMessagesCount > 0
                ? round(($webappMessagesCount / $totalWebappMessagesCount) * 100, 2)
                : 0,
        ];
    }
}
