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

    public function getStats($person, $seasonStart, $seasonEnd, $isWebmaster)
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
        $firstDate = $stmt->fetch()->min_date;

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

    public function getSeasonRange(string $seasonStart, string $seasonEnd): array
    {
        if ($seasonStart === '' || $seasonEnd === '') {
            $currentYear = date('Y');
            $currentMonth = date('m');

            if ($currentMonth < 9) {
                $seasonStart = ($currentYear - 1) . '-09-01';
                $seasonEnd = $currentYear . '-08-31';
            } else {
                $seasonStart = $currentYear . '-09-01';
                $seasonEnd = ($currentYear + 1) . '-08-31';
            }
        }
        return [
            'start' => $seasonStart,
            'end' => $seasonEnd
        ];
    }

    #region Private functions
    private function getArticleStats($personId, $seasonStart, $seasonEnd)
    {
        $userArticlesCount = $this->fluent
            ->from('Article')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->where('CreatedBy', $personId)
            ->where('LastUpdate BETWEEN ? AND ?', [$seasonStart, $seasonEnd])
            ->fetch('count');
        $totalArticlesCount = $this->fluent
            ->from('Article')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->where('LastUpdate BETWEEN ? AND ?', [$seasonStart, $seasonEnd])
            ->fetch('count');

        return [
            'user'       => $userArticlesCount,
            'total'      => $totalArticlesCount,
            'percentage' => $totalArticlesCount > 0
                ? round(($userArticlesCount / $totalArticlesCount) * 100, 2)
                : 0
        ];
    }

    private function getSurveyStats($personId, $seasonStart, $seasonEnd)
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
        $userSurveysCount = $userSurveys->fetch()->count;

        $query = "
            SELECT COUNT(*) as count 
            FROM Survey 
            WHERE IdArticle IN (
                SELECT Id FROM Article WHERE LastUpdate BETWEEN ? AND ?
            )
        ";
        $totalSurveys = $this->pdo->prepare($query);
        $totalSurveys->execute([$seasonStart, $seasonEnd]);
        $totalSurveysCount = $totalSurveys->fetch()->count;

        return [
            'user' => $userSurveysCount,
            'total' => $totalSurveysCount,
            'percentage' => $totalSurveysCount > 0 ? round(($userSurveysCount / $totalSurveysCount) * 100, 2) : 0
        ];
    }

    private function getSurveyRepliesStats($personId, $seasonStart, $seasonEnd)
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
        $userRepliesCount = $userReplies->fetch()->count;

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
        $totalRepliesCount = $totalReplies->fetch()->count;

        return [
            'user'       => $userRepliesCount,
            'total'      => $totalRepliesCount,
            'percentage' => $totalRepliesCount > 0
                ? round(($userRepliesCount / $totalRepliesCount) * 100, 2)
                : 0
        ];
    }

    private function getDesignStats($personId, $seasonStart, $seasonEnd)
    {
        $userDesignsCount = $this->fluent
            ->from('Design')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->where('IdPerson', $personId)
            ->where('datetime(LastUpdate) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');
        $totalDesignsCount = $this->fluent
            ->from('Design')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->where('datetime(LastUpdate) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');
        return [
            'user'       => $userDesignsCount,
            'total'      => $totalDesignsCount,
            'percentage' => $totalDesignsCount > 0
                ? round(($userDesignsCount / $totalDesignsCount) * 100, 2)
                : 0
        ];
    }

    private function getDesignVoteStats($personId, $seasonStart, $seasonEnd)
    {
        $userVotesCount = $this->fluent
            ->from('DesignVote dv')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->join('Design d ON dv.IdDesign = d.Id')
            ->where('dv.IdPerson', $personId)
            ->where('datetime(d.LastUpdate) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');
        $totalVotesCount = $this->fluent
            ->from('DesignVote dv')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->join('Design d ON dv.IdDesign = d.Id')
            ->where('datetime(d.LastUpdate) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');

        return [
            'user'       => $userVotesCount,
            'total'      => $totalVotesCount,
            'percentage' => $totalVotesCount > 0
                ? round(($userVotesCount / $totalVotesCount) * 100, 2)
                : 0
        ];
    }

    private function getEventStats($personId, $seasonStart, $seasonEnd)
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
        $totals = $stmt->fetch();

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
        $invitations = $stmt->fetch();

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

    private function getEventParticipationStats($personId, $seasonStart, $seasonEnd)
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

    private function getParticipantSupplyStats($personId, $seasonStart, $seasonEnd)
    {
        $userSuppliesCount = $this->fluent
            ->from('ParticipantSupply ps')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->join('Participant p ON ps.IdParticipant = p.Id')
            ->join('Event e ON p.IdEvent = e.Id')
            ->where('p.IdPerson', $personId)
            ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');
        $totalSuppliesCount = $this->fluent
            ->from('ParticipantSupply ps')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->join('Participant p ON ps.IdParticipant = p.Id')
            ->join('Event e ON p.IdEvent = e.Id')
            ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');
        return [
            'user'       => $userSuppliesCount,
            'total'      => $totalSuppliesCount,
            'percentage' => $totalSuppliesCount > 0
                ? round(($userSuppliesCount / $totalSuppliesCount) * 100, 2)
                : 0
        ];
    }

    private function getParticipantMessageStats($personId, $seasonStart, $seasonEnd)
    {
        $userMessagesCount = $this->fluent
            ->from('Message m')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->join('Event e ON m.EventId = e.Id')
            ->where('m.PersonId', $personId)
            ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->where('"From"', 'User')
            ->fetch('count');
        $totalUsersMessagesCount = $this->fluent
            ->from('Message m')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->join('Event e ON m.EventId = e.Id')
            ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->where('"From"', 'User')
            ->fetch('count');
        $webappMessagesCount = $this->fluent
            ->from('Message m')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->join('Event e ON m.EventId = e.Id')
            ->where('m.PersonId', $personId)
            ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->where('"From"', 'Webapp')
            ->fetch('count');
        $totalWebappMessagesCount = $this->fluent
            ->from('Message m')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->join('Event e ON m.EventId = e.Id')
            ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->where('"From"', 'Webapp')
            ->fetch('count');
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
