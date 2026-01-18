<?php

declare(strict_types=1);

namespace app\models;

use PDO;

use app\exceptions\UnauthorizedAccessException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\GravatarHandler;
use app\helpers\MyClubDateTime;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\interfaces\NewsProviderInterface;

class MessageDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function addMessage(?int $articleId, ?int $eventId, ?int $groupId, int $personId, string $text): int|false
    {
        $nonNullCount = ($articleId !== null) + ($eventId !== null) + ($groupId !== null);
        if ($nonNullCount !== 1) return false;
        if ($articleId !== null && $this->get('Article', ['Id' => $articleId]) === false) return false;
        if ($eventId !== null && $this->get('Event', ['Id' => $eventId]) === false) return false;
        if ($groupId !== null && $this->get('Group', ['Id' => $groupId]) === false) return false;

        $messageId = $this->set('Message', [
            'ArticleId' => $articleId,
            'EventId'   => $eventId,
            'GroupId'   => $groupId,
            'PersonId'  => $personId,
            'Text'      => $text,
            'From'      => 'User'
        ]);
        return $messageId;
    }

    public function addWebAppMessages(int $eventId, array $participants, string $text): array
    {
        $bccList = [];
        foreach ($participants as $participant) {
            $bccList[] = $participant->Email;
            $this->set('Message', [
                'EventId' => $eventId,
                'PersonId' => $participant->PersonId,
                'Text' => $text,
                'From' => 'Webapp'
            ]);
        }
        return $bccList;
    }

    public function getArticleMessages(int $articleId): array
    {
        $sql = "
            SELECT 
                Message.*,
                Person.FirstName,
                Person.LastName,
                Person.NickName,
                Person.Avatar,
                Person.UseGravatar,
                Person.Email
            FROM Message
            LEFT JOIN Person ON Message.PersonId = Person.Id
            WHERE Message.ArticleId = :articleId
            ORDER BY Message.Id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':articleId' => $articleId]);
        $messages = $stmt->fetchAll();
        $this->addAvatarAndTimeAgoToMessages($messages);
        return $messages;
    }

    public function getEventMessages(int $eventId): array
    {
        $sql = "
            SELECT 
                Message.*,
                Person.FirstName,
                Person.LastName,
                Person.NickName,
                Person.Avatar,
                Person.UseGravatar,
                Person.Email
            FROM Message
            LEFT JOIN Person ON Message.PersonId = Person.Id
            WHERE Message.EventId = :eventId AND Message.'From' = 'User'
            ORDER BY Message.Id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);
        $messages = $stmt->fetchAll();
        $this->addAvatarAndTimeAgoToMessages($messages);
        return $messages;
    }

    public function getGroupMessages(int $groupId): array
    {
        $sql = "
            SELECT 
                Message.*,
                Person.FirstName,
                Person.LastName,
                Person.NickName,
                Person.Avatar,
                Person.UseGravatar,
                Person.Email
            FROM Message
            LEFT JOIN Person ON Message.PersonId = Person.Id
            WHERE Message.GroupId = :groupId
            ORDER BY Message.Id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':groupId' => $groupId]);
        $messages = $stmt->fetchAll();
        $this->addAvatarAndTimeAgoToMessages($messages);
        return $messages;
    }

    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) return $news;
        $sql = "
            SELECT m.Id, m.Text, m.LastUpdate, m.EventId, p.FirstName, p.LastName, p.NickName, e.Summary, e.StartTime
            From Message m
            JOIN Person p ON p.Id = m.PersonId
            JOIN Event e ON e.Id = m.EventId
            WHERE m.LastUpdate > :searchFrom AND m.'From' = 'User' 
            AND m.EventId IN (SELECT IdEvent FROM Participant WHERE IdPerson = " . $connectedUser->person->Id . ")
            ORDER BY m.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':searchFrom' => $searchFrom]);
        $messages = $stmt->fetchAll();
        foreach ($messages as $message) {
            $news[] = [
                'type' => 'message',
                'id' => $message->EventId,
                'title' => $message->Summary . '(' . TranslationManager::getShortDate($message->StartTime) . ')' . ' => ' . $message->Text,
                'from' => $message->FirstName . ' ' . $message->LastName,
                'date' => $message->LastUpdate,
                'url' => '/event/chat/' . $message->EventId
            ];
        }
        return $news;
    }

    public function updateMessage(int $messageId, int $personId, string $text): true
    {
        $message = $this->get('Message', ['Id' => $messageId], 'PersonId');
        if (!$message || $message->PersonId != $personId) {
            throw new UnauthorizedAccessException("Vous n'êtes pas autorisé à modifier ce message");
        }
        $this->set('Message', ['Text' => $text, 'LastUpdate' =>  date('Y-m-d H:i:s')], ['Id' => $messageId]);
        return true;
    }


    public function getGroupedMessages(int $personId, string $searchFrom): array
    {
        $params = [];
        $whereClause = $searchFrom ? "AND m.LastUpdate >= ?" : '';
        $eventsQuery = "
        SELECT 
            e.Id,
            e.Summary AS title,
            e.LastUpdate,
            COUNT(m.Id) AS message_count,
            'event' AS type
        FROM Event e
        INNER JOIN Message m ON m.EventId = e.Id
        WHERE (
            m.'From' = 'User' AND (
                e.CreatedBy = ? 
                OR EXISTS (
                    SELECT 1 FROM Participant ep 
                    WHERE ep.IdEvent = e.Id 
                    AND ep.IdPerson = ?
                )
            )
        )
        $whereClause
        GROUP BY e.Id, e.Summary, e.LastUpdate
        HAVING message_count > 0";
        $params[] = $personId;
        $params[] = $personId;
        if ($searchFrom) $params[] = $searchFrom;

        $articlesQuery = "
        SELECT 
            a.Id,
            a.Title AS title,
            a.LastUpdate,
            COUNT(m.Id) AS message_count,
            'article' AS type
        FROM Article a
        INNER JOIN Message m ON m.ArticleId = a.Id
        WHERE (
            a.CreatedBy = ? 
            OR a.IdGroup IN (
                SELECT gm.IdGroup 
                FROM PersonGroup gm 
                WHERE gm.IdPerson = ?
            )
            OR a.IdGroup IS NULL
        )
        $whereClause
        GROUP BY a.Id, a.Title, a.LastUpdate
        HAVING message_count > 0";
        $params[] = $personId;
        $params[] = $personId;
        if ($searchFrom) $params[] = $searchFrom;

        $groupsQuery = "
        SELECT 
            g.Id,
            g.Name AS title,
            MAX(m.LastUpdate) AS LastUpdate,
            COUNT(m.Id) AS message_count,
            'group' AS type
        FROM `Group` g
        INNER JOIN PersonGroup gm ON gm.IdGroup = g.Id
        INNER JOIN Message m ON m.GroupId = g.Id
        WHERE gm.IdPerson = ?
        $whereClause
        GROUP BY g.Id, g.Name
        HAVING message_count > 0";
        $params[] = $personId;
        if ($searchFrom) $params[] = $searchFrom;

        $query = "
        $eventsQuery
        UNION ALL
        $articlesQuery
        UNION ALL
        $groupsQuery
        ORDER BY LastUpdate DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    #region Private functions
    private function addAvatarAndTimeAgoToMessages(array $messages): array
    {
        static $gravatarHandler = new GravatarHandler();

        foreach ($messages as $message) {
            $message->UserImg = WebApp::getUserImg($message, $gravatarHandler);
            $message->TimeAgo = MyClubDateTime::calculateTimeAgo($message->LastUpdate);
        }
        return $messages;
    }
}
