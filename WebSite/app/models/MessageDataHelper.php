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
        $messages = $stmt->fetchAll(PDO::FETCH_OBJ);
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
        $messages = $stmt->fetchAll(PDO::FETCH_OBJ);
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
        $messages = $stmt->fetchAll(PDO::FETCH_OBJ);
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
        $messages = $stmt->fetchAll(PDO::FETCH_OBJ);
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

    public function getGroupedMessages(int $personId, string $searchFrom, GravatarHandler $gravatarHandler): array
    {
        $params = [];
        $whereClause = $searchFrom ? "AND m.LastUpdate >= ?" : '';

        $eventsQuery = "
        SELECT 
            e.Id,
            e.Summary AS title,
            datetime(MAX(m.LastUpdate), 'localtime') AS LastUpdate,
            COUNT(m.Id) AS message_count,
            'event' AS type,
            lp_e.Avatar,
            lp_e.UseGravatar,
            lp_e.Email
        FROM Event e
        INNER JOIN EventType et ON e.IdEventType = et.Id
        INNER JOIN Message m ON m.EventId = e.Id AND m.\"From\" = 'User'
        LEFT JOIN (
            SELECT m2.EventId, p.Avatar, p.UseGravatar, p.Email
            FROM Message m2
            INNER JOIN Person p ON p.Id = m2.PersonId
            WHERE m2.`From` = 'User'
            AND m2.LastUpdate = (
                SELECT MAX(m3.LastUpdate)
                FROM Message m3
                WHERE m3.EventId = m2.EventId
                    AND m3.`From` = 'User'
            )
        ) lp_e ON lp_e.EventId = e.Id
        WHERE et.Inactivated = 0
        AND (
            et.IdGroup IS NULL 
            OR et.IdGroup IN (
                SELECT IdGroup 
                FROM PersonGroup 
                WHERE IdPerson = ?
            )
        )
        $whereClause
        GROUP BY e.Id, e.Summary, lp_e.Avatar, lp_e.UseGravatar
        HAVING message_count > 0";

        $params[] = $personId;
        if ($searchFrom) $params[] = $searchFrom;

        $articlesQuery = "
        SELECT 
            a.Id,
            a.Title AS title,
            datetime(MAX(m.LastUpdate), 'localtime') AS LastUpdate,
            COUNT(m.Id) AS message_count,
            'article' AS type,
            lp_a.Avatar,
            lp_a.UseGravatar,
            lp_a.Email
        FROM Article a
        INNER JOIN Message m ON m.ArticleId = a.Id AND m.\"From\" = 'User'
        LEFT JOIN (
            SELECT m2.ArticleId, p.Avatar, p.UseGravatar, p.Email
            FROM Message m2
            INNER JOIN Person p ON p.Id = m2.PersonId
            WHERE m2.`From` = 'User'
            AND m2.LastUpdate = (
                SELECT MAX(m3.LastUpdate)
                FROM Message m3
                WHERE m3.ArticleId = m2.ArticleId
                    AND m3.`From` = 'User'
            )
        ) lp_a ON lp_a.ArticleId = a.Id
        WHERE a.PublishedBy IS NOT NULL
        AND (
            a.CreatedBy = ?
            OR a.IdGroup IS NULL
            OR a.IdGroup IN (
                SELECT IdGroup 
                FROM PersonGroup 
                WHERE IdPerson = ?
            )
        )
        $whereClause
        GROUP BY a.Id, a.Title, lp_a.Avatar, lp_a.UseGravatar
        HAVING message_count > 0";

        $params[] = $personId;
        $params[] = $personId;
        if ($searchFrom) $params[] = $searchFrom;

        $groupsQuery = "
        SELECT 
            g.Id,
            g.Name AS title,
            datetime(MAX(m.LastUpdate), 'localtime') AS LastUpdate,
            COUNT(m.Id) AS message_count,
            'group' AS type,
            lp_g.Avatar,
            lp_g.UseGravatar,
            lp_g.Email
        FROM `Group` g
        LEFT JOIN PersonGroup pg ON pg.IdGroup = g.Id AND pg.IdPerson = ?
        INNER JOIN Message m ON m.GroupId = g.Id AND m.\"From\" = 'User'
        LEFT JOIN (
            SELECT m2.GroupId, p.Avatar, p.UseGravatar, p.Email
            FROM Message m2
            INNER JOIN Person p ON p.Id = m2.PersonId
            WHERE m2.`From` = 'User'
            AND m2.LastUpdate = (
                SELECT MAX(m3.LastUpdate)
                FROM Message m3
                WHERE m3.GroupId = m2.GroupId
                    AND m3.`From` = 'User'
            )
        ) lp_g ON lp_g.GroupId = g.Id
        WHERE g.Inactivated = 0
        AND (g.SelfRegistration = 1 OR pg.Id IS NOT NULL)
        $whereClause
        GROUP BY g.Id, g.Name, lp_g.Avatar, lp_g.UseGravatar
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
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        return array_map(function (object $row) use ($gravatarHandler): array {
            $userImg = WebApp::getUserImg($row, $gravatarHandler);

            $result = (array) $row;
            $result['UserImg'] = $userImg;
            unset($result['Avatar'], $result['UseGravatar'], $result['Email']);

            return $result;
        }, $rows);
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
