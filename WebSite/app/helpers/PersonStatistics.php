<?php

namespace app\helpers;

use PDO;

class PersonStatistics
{
    private PDO $pdo;
    private $fluent;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
    }

    public function getStats($person, $seasonStart, $seasonEnd)
    {
        $stats = [
            'person' => $person,
            'seasonStart' => $seasonStart,
            'seasonEnd' => $seasonEnd,
            'articles' => $this->getArticleStats($person['Id'], $seasonStart, $seasonEnd),
            'surveys' => $this->getSurveyStats($person['Id'], $seasonStart, $seasonEnd),
            'surveyReplies' => $this->getSurveyRepliesStats($person['Id'], $seasonStart, $seasonEnd),
            'designs' => $this->getDesignStats($person['Id'], $seasonStart, $seasonEnd),
            'designVotes' => $this->getDesignVoteStats($person['Id'], $seasonStart, $seasonEnd),
            'events' => $this->getEventStats($person['Id'], $seasonStart, $seasonEnd),
            'eventParticipations' => $this->getEventParticipationStats($person['Id'], $seasonStart, $seasonEnd),
            'participantSupplies' => $this->getParticipantSupplyStats($person['Id'], $seasonStart, $seasonEnd),
        ];

        return $stats;
    }

    public function getAvailableSeasons()
    {
        $query = "
            SELECT MIN(Timestamp) as min_date FROM (
                SELECT MIN(Timestamp) as Timestamp FROM Article
                UNION
                SELECT MIN(StartTime) as Timestamp FROM Event
            )
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $firstDate = $stmt->fetch(PDO::FETCH_ASSOC)['min_date'];

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

    public function getSeasonRange(): array {
        $seasonStart = $_GET['seasonStart'] ?? null;
        $seasonEnd = $_GET['seasonEnd'] ?? null;
    
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
    

    private function getArticleStats($personId, $seasonStart, $seasonEnd)
    {
        $query = "
            SELECT COUNT(*) as count 
            FROM Article 
            WHERE CreatedBy = ? 
            AND Timestamp BETWEEN ? AND ?
        ";
        $userArticles = $this->pdo->prepare($query);
        $userArticles->execute([$personId, $seasonStart, $seasonEnd]);
        $userArticlesCount = $userArticles->fetch(PDO::FETCH_ASSOC)['count'];

        $query = "
            SELECT COUNT(*) as count 
            FROM Article 
            WHERE Timestamp BETWEEN ? AND ?
        ";
        $totalArticles = $this->pdo->prepare($query);
        $totalArticles->execute([$seasonStart, $seasonEnd]);
        $totalArticlesCount = $totalArticles->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'user' => $userArticlesCount,
            'total' => $totalArticlesCount,
            'percentage' => $totalArticlesCount > 0 ? round(($userArticlesCount / $totalArticlesCount) * 100, 2) : 0
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
                    SELECT Id FROM Article WHERE Timestamp BETWEEN ? AND ?
                )
            )
        ";
        $userSurveys = $this->pdo->prepare($query);
        $userSurveys->execute([$personId, $seasonStart, $seasonEnd]);
        $userSurveysCount = $userSurveys->fetch(PDO::FETCH_ASSOC)['count'];

        $query = "
            SELECT COUNT(*) as count 
            FROM Survey 
            WHERE IdArticle IN (
                SELECT Id FROM Article WHERE Timestamp BETWEEN ? AND ?
            )
        ";
        $totalSurveys = $this->pdo->prepare($query);
        $totalSurveys->execute([$seasonStart, $seasonEnd]);
        $totalSurveysCount = $totalSurveys->fetch(PDO::FETCH_ASSOC)['count'];

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
                WHERE a.Timestamp BETWEEN ? AND ?
            )
        ";
        $userReplies = $this->pdo->prepare($query);
        $userReplies->execute([$personId, $seasonStart, $seasonEnd]);
        $userRepliesCount = $userReplies->fetch(PDO::FETCH_ASSOC)['count'];

        $query = "
            SELECT COUNT(*) as count 
            FROM Reply
            WHERE Id IN (
                SELECT r.Id FROM Reply r
                JOIN Survey s ON r.IdSurvey = s.Id
                JOIN Article a ON s.IdArticle = a.Id
                WHERE a.Timestamp BETWEEN ? AND ?
            )
        ";
        $totalReplies = $this->pdo->prepare($query);
        $totalReplies->execute([$seasonStart, $seasonEnd]);
        $totalRepliesCount = $totalReplies->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'user' => $userRepliesCount,
            'total' => $totalRepliesCount,
            'percentage' => $totalRepliesCount > 0 ? round(($userRepliesCount / $totalRepliesCount) * 100, 2) : 0
        ];
    }

    private function getDesignStats($personId, $seasonStart, $seasonEnd)
    {
        $query = "
            SELECT COUNT(*) as count 
            FROM Design 
            WHERE IdPerson = ? 
            AND datetime(LastUpdate) BETWEEN datetime(?) AND datetime(?)
        ";
        $userDesigns = $this->pdo->prepare($query);
        $userDesigns->execute([$personId, $seasonStart, $seasonEnd]);
        $userDesignsCount = $userDesigns->fetch(PDO::FETCH_ASSOC)['count'];

        $query = "
            SELECT COUNT(*) as count 
            FROM Design 
            WHERE datetime(LastUpdate) BETWEEN datetime(?) AND datetime(?)
        ";
        $totalDesigns = $this->pdo->prepare($query);
        $totalDesigns->execute([$seasonStart, $seasonEnd]);
        $totalDesignsCount = $totalDesigns->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'user' => $userDesignsCount,
            'total' => $totalDesignsCount,
            'percentage' => $totalDesignsCount > 0 ? round(($userDesignsCount / $totalDesignsCount) * 100, 2) : 0
        ];
    }

    private function getDesignVoteStats($personId, $seasonStart, $seasonEnd)
    {
        $query = "
            SELECT COUNT(*) as count 
            FROM DesignVote 
            WHERE IdPerson = ? 
            AND Id IN (
                SELECT dv.Id FROM DesignVote dv
                JOIN Design d ON dv.IdDesign = d.Id
                WHERE datetime(d.LastUpdate) BETWEEN datetime(?) AND datetime(?)
            )
        ";
        $userVotes = $this->pdo->prepare($query);
        $userVotes->execute([$personId, $seasonStart, $seasonEnd]);
        $userVotesCount = $userVotes->fetch(PDO::FETCH_ASSOC)['count'];

        $query = "
            SELECT COUNT(*) as count 
            FROM DesignVote
            WHERE Id IN (
                SELECT dv.Id FROM DesignVote dv
                JOIN Design d ON dv.IdDesign = d.Id
                WHERE datetime(d.LastUpdate) BETWEEN datetime(?) AND datetime(?)
            )
        ";
        $totalVotes = $this->pdo->prepare($query);
        $totalVotes->execute([$seasonStart, $seasonEnd]);
        $totalVotesCount = $totalVotes->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'user' => $userVotesCount,
            'total' => $totalVotesCount,
            'percentage' => $totalVotesCount > 0 ? round(($userVotesCount / $totalVotesCount) * 100, 2) : 0
        ];
    }

    private function getEventStats($personId, $seasonStart, $seasonEnd)
    {
        $eventTypes = $this->fluent->from('EventType')->fetchAll();

        $stats = [];
        
        foreach ($eventTypes as $eventType) {
            $query = "
                SELECT COUNT(*) as count 
                FROM Event 
                WHERE CreatedBy = ? 
                AND IdEventType = ?
                AND datetime(StartTime) BETWEEN datetime(?) AND datetime(?)
            ";
            $userEvents = $this->pdo->prepare($query);
            $userEvents->execute([$personId, $eventType['Id'], $seasonStart, $seasonEnd]);
            $userEventsCount = $userEvents->fetch(PDO::FETCH_ASSOC)['count'];

            $query = "
                SELECT COUNT(*) as count 
                FROM Event 
                WHERE IdEventType = ?
                AND datetime(StartTime) BETWEEN datetime(?) AND datetime(?)
            ";
            $totalEvents = $this->pdo->prepare($query);
            $totalEvents->execute([$eventType['Id'], $seasonStart, $seasonEnd]);
            $totalEventsCount = $totalEvents->fetch(PDO::FETCH_ASSOC)['count'];

            $stats[$eventType['Id']] = [
                'typeName' => $eventType['Name'],
                'user' => $userEventsCount,
                'total' => $totalEventsCount,
                'percentage' => $totalEventsCount > 0 ? round(($userEventsCount / $totalEventsCount) * 100, 2) : 0
            ];
        }

        $query = "
            SELECT COUNT(*) as count 
            FROM Event 
            WHERE CreatedBy = ? 
            AND datetime(StartTime) BETWEEN datetime(?) AND datetime(?)
        ";
        $userAllEvents = $this->pdo->prepare($query);
        $userAllEvents->execute([$personId, $seasonStart, $seasonEnd]);
        $userAllEventsCount = $userAllEvents->fetch(PDO::FETCH_ASSOC)['count'];

        $query = "
            SELECT COUNT(*) as count 
            FROM Event 
            WHERE datetime(StartTime) BETWEEN datetime(?) AND datetime(?)
        ";
        $totalAllEvents = $this->pdo->prepare($query);
        $totalAllEvents->execute([$seasonStart, $seasonEnd]);
        $totalAllEventsCount = $totalAllEvents->fetch(PDO::FETCH_ASSOC)['count'];

        $stats['total'] = [
            'typeName' => 'Total',
            'user' => $userAllEventsCount,
            'total' => $totalAllEventsCount,
            'percentage' => $totalAllEventsCount > 0 ? round(($userAllEventsCount / $totalAllEventsCount) * 100, 2) : 0
        ];

        return $stats;
    }

    private function getEventParticipationStats($personId, $seasonStart, $seasonEnd)
    {
        $eventTypes = $this->fluent->from('EventType')->fetchAll();

        $stats = [];
        
        foreach ($eventTypes as $eventType) {
            $query = "
                SELECT COUNT(*) as count 
                FROM Participant p
                JOIN Event e ON p.IdEvent = e.Id
                WHERE p.IdPerson = ? 
                AND e.IdEventType = ?
                AND datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
            ";
            $userParticipations = $this->pdo->prepare($query);
            $userParticipations->execute([$personId, $eventType['Id'], $seasonStart, $seasonEnd]);
            $userParticipationsCount = $userParticipations->fetch(PDO::FETCH_ASSOC)['count'];

            $query = "
                SELECT COUNT(*) as count 
                FROM Participant p
                JOIN Event e ON p.IdEvent = e.Id
                WHERE e.IdEventType = ?
                AND datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
            ";
            $totalParticipations = $this->pdo->prepare($query);
            $totalParticipations->execute([$eventType['Id'], $seasonStart, $seasonEnd]);
            $totalParticipationsCount = $totalParticipations->fetch(PDO::FETCH_ASSOC)['count'];

            $stats[$eventType['Id']] = [
                'typeName' => $eventType['Name'],
                'user' => $userParticipationsCount,
                'total' => $totalParticipationsCount,
                'percentage' => $totalParticipationsCount > 0 ? round(($userParticipationsCount / $totalParticipationsCount) * 100, 2) : 0
            ];
        }

        $query = "
            SELECT COUNT(*) as count 
            FROM Participant p
            JOIN Event e ON p.IdEvent = e.Id
            WHERE p.IdPerson = ? 
            AND datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
        ";
        $userAllParticipations = $this->pdo->prepare($query);
        $userAllParticipations->execute([$personId, $seasonStart, $seasonEnd]);
        $userAllParticipationsCount = $userAllParticipations->fetch(PDO::FETCH_ASSOC)['count'];

        $query = "
            SELECT COUNT(*) as count 
            FROM Participant p
            JOIN Event e ON p.IdEvent = e.Id
            WHERE datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
        ";
        $totalAllParticipations = $this->pdo->prepare($query);
        $totalAllParticipations->execute([$seasonStart, $seasonEnd]);
        $totalAllParticipationsCount = $totalAllParticipations->fetch(PDO::FETCH_ASSOC)['count'];

        $stats['total'] = [
            'typeName' => 'Total',
            'user' => $userAllParticipationsCount,
            'total' => $totalAllParticipationsCount,
            'percentage' => $totalAllParticipationsCount > 0 ? round(($userAllParticipationsCount / $totalAllParticipationsCount) * 100, 2) : 0
        ];

        return $stats;
    }

    private function getParticipantSupplyStats($personId, $seasonStart, $seasonEnd)
    {
        $query = "
            SELECT COUNT(*) as count 
            FROM ParticipantSupply ps
            JOIN Participant p ON ps.IdParticipant = p.Id
            JOIN Event e ON p.IdEvent = e.Id
            WHERE p.IdPerson = ? 
            AND datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
        ";
        $userSupplies = $this->pdo->prepare($query);
        $userSupplies->execute([$personId, $seasonStart, $seasonEnd]);
        $userSuppliesCount = $userSupplies->fetch(PDO::FETCH_ASSOC)['count'];

        $query = "
            SELECT COUNT(*) as count 
            FROM ParticipantSupply ps
            JOIN Participant p ON ps.IdParticipant = p.Id
            JOIN Event e ON p.IdEvent = e.Id
            WHERE datetime(e.StartTime) BETWEEN datetime(?) AND datetime(?)
        ";
        $totalSupplies = $this->pdo->prepare($query);
        $totalSupplies->execute([$seasonStart, $seasonEnd]);
        $totalSuppliesCount = $totalSupplies->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'user' => $userSuppliesCount,
            'total' => $totalSuppliesCount,
            'percentage' => $totalSuppliesCount > 0 ? round(($userSuppliesCount / $totalSuppliesCount) * 100, 2) : 0
        ];
    }
}