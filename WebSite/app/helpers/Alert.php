<?php

namespace app\helpers;

use PDO;

class Alert
{
    protected PDO $pdo;
    protected $fluent;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
    }

    public function getAlerts()
    {
        $query = "
        SELECT a.*, 
               p.FirstName, p.LastName, p.NickName, 
               g.Name as GroupName
        FROM Alert a
        JOIN Person p ON a.CreatedBy = p.Id
        JOIN 'Group' g ON a.IdGroup = g.Id
        ORDER BY a.StartDate DESC
    ";

        return $this->pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAlertById($id)
    {
        $query = "
        SELECT a.*, 
               p.FirstName, p.LastName, p.NickName, 
               g.Name as GroupName
        FROM Alert a
        JOIN Person p ON a.CreatedBy = p.Id
        JOIN 'Group' g ON a.IdGroup = g.Id
        WHERE a.Id = :id
    ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createAlert($createdBy, $groupId, $message, $type, $startDate, $endDate, $onlyForMembers)
    {
        $query = "
            INSERT INTO Alert (CreatedBy, IdGroup, Message, Type, StartDate, EndDate, OnlyForMembers, LastUpdate)
            VALUES (:createdBy, :groupId, :message, :type, :startDate, :endDate, :onlyForMembers, datetime('now'))
        ";

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([
            'createdBy' => $createdBy,
            'groupId' => $groupId,
            'message' => $message,
            'type' => $type,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'onlyForMembers' => $onlyForMembers
        ]);
    }

    public function updateAlert($id, $message, $type, $startDate, $endDate, $onlyForMembers)
    {
        $query = "
            UPDATE Alert 
            SET Message = :message,
                Type = :type,
                StartDate = :startDate,
                EndDate = :endDate,
                OnlyForMembers = :onlyForMembers,
                LastUpdate = datetime('now')
            WHERE Id = :id
        ";

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([
            'id' => $id,
            'message' => $message,
            'type' => $type,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'onlyForMembers' => $onlyForMembers
        ]);
    }

    public function getPendingSurveyResponses()
    {
        $query = "
        SELECT 
            p.Id AS PersonId, 
            p.Email, 
            a.Id AS ArticleId, 
            a.Title AS ArticleTitle, 
            s.Id AS SurveyId, 
            s.Question AS SurveyQuestion, 
            s.ClosingDate
        FROM Person p
        CROSS JOIN Survey s
        JOIN Article a ON s.IdArticle = a.Id
        LEFT JOIN Reply r ON r.IdSurvey = s.Id AND r.IdPerson = p.Id
        LEFT JOIN PersonGroup pg ON pg.IdPerson = p.Id AND pg.IdGroup = a.IdGroup
        WHERE 
            a.PublishedBy IS NOT NULL
            AND p.Inactivated = 0
            AND s.ClosingDate > date('now')
            AND (
                a.IdGroup IS NULL
                OR pg.IdGroup IS NOT NULL 
            )
            AND r.Id IS NULL
        ORDER BY s.ClosingDate, p.LastName, p.FirstName;";

        return $this->pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
}
