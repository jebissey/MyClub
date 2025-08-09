<?php

namespace app\models;

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
            'membersAlerts' => $this->getMembersAlerts($isWebmaster),
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

    public function getSeasonRange(?string $seasonStart, ?string $seasonEnd): array
    {
        if ($seasonStart === null || $seasonEnd === null) {
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
        $eventTypes = $this->fluent->from('EventType')->fetchAll();
        $stats = [];
        foreach ($eventTypes as $eventType) {
            $userEventsCount = $this->fluent
                ->from('Event')
                ->select(null)
                ->select('COUNT(*) AS count')
                ->where('CreatedBy', $personId)
                ->where('IdEventType', $eventType->Id)
                ->where('datetime(StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
                ->fetch('count');
            $totalEventsCount = $this->fluent
                ->from('Event')
                ->select(null)
                ->select('COUNT(*) AS count')
                ->where('IdEventType', $eventType->Id)
                ->where('datetime(StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
                ->fetch('count');
            $stats[$eventType->Id] = [
                'typeName'   => $eventType->Name,
                'user'       => $userEventsCount,
                'total'      => $totalEventsCount,
                'percentage' => $totalEventsCount > 0
                    ? round(($userEventsCount / $totalEventsCount) * 100, 2)
                    : 0
            ];
        }

        $userAllEventsCount = $this->fluent
            ->from('Event')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->where('CreatedBy', $personId)
            ->where('datetime(StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');
        $totalAllEventsCount = $this->fluent
            ->from('Event')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->where('datetime(StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');
        $stats['total'] = [
            'typeName'   => 'Total',
            'user'       => $userAllEventsCount,
            'total'      => $totalAllEventsCount,
            'percentage' => $totalAllEventsCount > 0
                ? round(($userAllEventsCount / $totalAllEventsCount) * 100, 2)
                : 0
        ];

        $userInvitationCount = $this->fluent
            ->from('Guest g')
            ->innerJoin('Event e ON e.Id = g.IdEvent')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->where('InvitedBy', $personId)
            ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');
        if ($userInvitationCount > 0) {
            $totalInvitationCount = $this->fluent
                ->from('Guest g')
                ->innerJoin('Event e ON e.Id = g.IdEvent')
                ->select(null)
                ->select('COUNT(*) AS count')
                ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
                ->fetch('count');
            $stats['invitation'] = [
                'typeName'   => 'Invitation envoyées',
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
        $eventTypes = $this->fluent->from('EventType')->fetchAll();

        $stats = [];

        foreach ($eventTypes as $eventType) {
            $userParticipationsCount = $this->fluent
                ->from('Participant p')
                ->select(null)
                ->select('COUNT(*) AS count')
                ->join('Event e ON p.IdEvent = e.Id')
                ->where('p.IdPerson', $personId)
                ->where('e.IdEventType', $eventType->Id)
                ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
                ->fetch('count');
            $totalParticipationsCount = $this->fluent
                ->from('Participant p')
                ->select(null)
                ->select('COUNT(*) AS count')
                ->join('Event e ON p.IdEvent = e.Id')
                ->where('e.IdEventType', $eventType->Id)
                ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
                ->fetch('count');

            $stats[$eventType->Id] = [
                'typeName'   => $eventType->Name,
                'user'       => $userParticipationsCount,
                'total'      => $totalParticipationsCount,
                'percentage' => $totalParticipationsCount > 0
                    ? round(($userParticipationsCount / $totalParticipationsCount) * 100, 2)
                    : 0
            ];
        }
        $userAllParticipationsCount = $this->fluent
            ->from('Participant p')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->join('Event e ON p.IdEvent = e.Id')
            ->where('p.IdPerson', $personId)
            ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');
        $totalAllParticipationsCount = $this->fluent
            ->from('Participant p')
            ->select(null)
            ->select('COUNT(*) AS count')
            ->join('Event e ON p.IdEvent = e.Id')
            ->where('datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)', [$seasonStart, $seasonEnd])
            ->fetch('count');

        $stats['total'] = [
            'typeName'   => 'Total',
            'user'       => $userAllParticipationsCount,
            'total'      => $totalAllParticipationsCount,
            'percentage' => $totalAllParticipationsCount > 0
                ? round(($userAllParticipationsCount / $totalAllParticipationsCount) * 100, 2)
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

    private function getMembersAlerts($isWebmaster)
    {
        $membersAlerts = [];
        if ($isWebmaster) {
            $query = "
            SELECT 
                p.FirstName || ' ' || p.LastName || 
                CASE 
                    WHEN p.NickName IS NOT NULL AND p.NickName != '' THEN ' (' || p.NickName || ')'
                    ELSE ''
                END AS clubMember,
                CASE 
                    WHEN p.Preferences LIKE '%noAlerts%' THEN 'X'
                    ELSE ''
                END AS NoAlert,
                CASE 
                    WHEN p.Preferences LIKE '%newEvent%' THEN 'X'
                    ELSE ''
                END AS NewEvent,
                CASE 
                    WHEN p.Preferences LIKE '%newArticle%' THEN 'X'
                    ELSE ''
                END AS NewArticle
            FROM Person AS p
            WHERE p.Preferences LIKE '%noAlerts%' 
            OR p.Preferences LIKE '%newEvent%' 
            OR p.Preferences LIKE '%newArticle%'
            ORDER BY clubMember
            ";
            $stmt = $this->pdo->query($query);
            $membersAlerts = $stmt->fetchAll();
        }
        return $membersAlerts;
    }
}
