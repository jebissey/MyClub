<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use RuntimeException;
use stdClass;
use Throwable;
use app\exceptions\UnauthorizedAccessException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\GravatarHandler;
use app\helpers\MyClubDateTime;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\modules\Common\interfaces\NewsProviderInterface;
use app\modules\Common\valueObjects\MessageTarget;
use app\modules\Event\valueObjects\EventParticipant;

final class MessageDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(Application $application, private readonly LanguagesDataHelper $languagesDataHelper)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    public function addMessage(
        MessageTarget $target,
        int $personId,
        string $text,
        ?string $imagePath = null,
    ): int|false {
        [$table, $id] = $target->reference();
        if ($this->get($table, ['Id' => $id], 'Id') === false) {
            return false;
        }

        $messageId = $this->set('Message', [
            'ArticleId' => $target->articleId,
            'EventId'   => $target->eventId,
            'GroupId'   => $target->groupId,
            'PersonId'  => $personId,
            'Text'      => $text,
            'From'      => 'User',
            'ImagePath' => $imagePath,
        ]);

        return is_int($messageId) ? $messageId : false;
    }

    /**
     * @param list<EventParticipant> $participants
     * @return array<int, string>
     */
    public function addWebAppMessages(int $eventId, array $participants, string $text): array
    {
        $bccList = [];
        foreach ($participants as $participant) {
            $bccList[] = $participant->email;
            $this->set('Message', [
                'EventId'  => $eventId,
                'PersonId' => $participant->personId,
                'Text'     => $text,
                'From'     => 'Webapp'
            ]);
        }
        return $bccList;
    }

    public function countUserMessagesForArticle(int $articleId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Message WHERE "From" = :from AND ArticleId = :articleId');
        $stmt->execute([':from' => 'User', ':articleId' => $articleId]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, stdClass> */
    public function getArticleMessages(int $articleId): array
    {
        $sql = "
            SELECT 
                Message.*,
                Individual.FirstName,
                Individual.LastName,
                Individual.NickName,
                Individual.Avatar,
                Member.UseGravatar,
                Individual.Email
            FROM Message
            LEFT JOIN Member     ON Message.PersonId = Member.Id
            LEFT JOIN Individual ON Member.Id = Individual.Id
            WHERE Message.ArticleId = :articleId
            ORDER BY Message.Id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':articleId' => $articleId]);

        /** @var list<stdClass> $messages */
        $messages = $stmt->fetchAll(PDO::FETCH_OBJ);

        $this->addAvatarAndTimeAgoToMessages($messages);
        return $messages;
    }

    /** @return array<int, stdClass> */
    public function getEventMessages(int $eventId): array
    {
        $sql = "
            SELECT 
                Message.*,
                Individual.FirstName,
                Individual.LastName,
                Individual.NickName,
                Individual.Avatar,
                Member.UseGravatar,
                Individual.Email
            FROM Message
            LEFT JOIN Member     ON Message.PersonId = Member.Id
            LEFT JOIN Individual ON Member.Id = Individual.Id
            WHERE Message.EventId = :eventId AND Message.\"From\" = 'User'
            ORDER BY Message.Id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);

        /** @var list<stdClass> $messages */
        $messages = $stmt->fetchAll(PDO::FETCH_OBJ);

        $this->addAvatarAndTimeAgoToMessages($messages);
        return $messages;
    }

    /** @return array<int, stdClass> */
    public function getGroupMessages(int $groupId): array
    {
        $sql = "
            SELECT 
                Message.Id,
                Message.PersonId,
                Message.Text,
                Message.ImagePath,
                Message.LastUpdate,
                Individual.FirstName,
                Individual.LastName,
                Individual.NickName,
                Individual.Avatar,
                Member.UseGravatar,
                Individual.Email
            FROM Message
            LEFT JOIN Member     ON Message.PersonId = Member.Id
            LEFT JOIN Individual ON Member.Id = Individual.Id
            WHERE Message.GroupId = :groupId
            ORDER BY Message.Id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':groupId' => $groupId]);

        /** @var list<stdClass> $messages */
        $messages = $stmt->fetchAll(PDO::FETCH_OBJ);

        $this->addAvatarAndTimeAgoToMessages($messages);
        return $messages;
    }

    /** @return array<int, array<string, mixed>> */
    public function getGroupedMessages(int $personId, string $searchFrom, GravatarHandler $gravatarHandler): array
    {
        $rows = $this->doGetGroupedMessages($personId, $searchFrom);

        /** @var list<array<string, mixed>> $result */
        $result = array_map(function (stdClass $row) use ($gravatarHandler): array {
            $userImg = WebApp::computeUserImg(
                (bool) ($row->UseGravatar ?? false),
                is_string($row->Email ?? null) ? $row->Email : null,
                is_string($row->Avatar ?? null) ? $row->Avatar : null,
                $gravatarHandler
            );

            /** @var array<string, mixed> $item */
            $item = (array) $row;
            $item['UserImg'] = $userImg;
            unset($item['Avatar'], $item['UseGravatar'], $item['Email']);

            return $item;
        }, $rows);

        return $result;
    }

    /** @return array{0: int, 1: int, 2: string} */
    public function getImageInfoFromMessage(int $messageId): array
    {
        $message = $this->get('Message', ['Id' => $messageId], 'ImagePath');
        if (!$message) {
            throw new RuntimeException(
                $this->languagesDataHelper->translate('message.image_not_found')
            );
        }
        if (empty($message->ImagePath)) {
            throw new RuntimeException(
                $this->languagesDataHelper->translate('message.image_not_attached')
            );
        }
        $path = is_string($message->ImagePath)
            ? parse_url($message->ImagePath, PHP_URL_PATH)
            : false;

        if (!is_string($path) || $path === '') {
            throw new RuntimeException(
                $this->languagesDataHelper->translate('message.image_invalid_path')
            );
        }
        $filename = basename($path);
        $month = basename(dirname($path));
        $year = basename(dirname(dirname($path)));
        if (!is_numeric($year) || !is_numeric($month) || empty($filename)) {
            throw new RuntimeException(
                $this->languagesDataHelper->translate('message.image_invalid_structure')
            );
        }
        return [(int)$year, (int)$month, $filename];
    }

    /**
     * @param array{start: string, end: string} $season
     * @return array<string, int>
     */
    public function getMessages(array $season): array
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

    /**
     * @return array{
     *     events: array<int, stdClass>,
     *     articles: array<int, stdClass>,
     *     groups: array<int, stdClass>
     * }
     */
    public function getMessageUses(string $path): array
    {
        $like = '%' . $path . '%';

        $events = $this->pdo->prepare("
            SELECT DISTINCT e.Id, e.Summary AS Title, e.StartTime
            FROM Message m
            JOIN Event e ON e.Id = m.EventId
            WHERE m.EventId IS NOT NULL
              AND m.ImagePath LIKE :path
            ORDER BY e.StartTime DESC
        ");
        $events->execute([':path' => $like]);

        $articles = $this->pdo->prepare("
            SELECT DISTINCT a.Id, a.Title
            FROM Message m
            JOIN Article a ON a.Id = m.ArticleId
            WHERE m.ArticleId IS NOT NULL
              AND m.ImagePath LIKE :path
            ORDER BY a.Title ASC
        ");
        $articles->execute([':path' => $like]);

        $groups = $this->pdo->prepare("
            SELECT DISTINCT g.Id, g.Name AS Title
            FROM Message m
            JOIN `Group` g ON g.Id = m.GroupId
            WHERE m.GroupId IS NOT NULL
              AND m.ImagePath LIKE :path
            ORDER BY g.Name ASC
        ");
        $groups->execute([':path' => $like]);

        /** @var list<stdClass> $eventsResult */
        $eventsResult = $events->fetchAll(PDO::FETCH_OBJ);

        /** @var list<stdClass> $articlesResult */
        $articlesResult = $articles->fetchAll(PDO::FETCH_OBJ);

        /** @var list<stdClass> $groupsResult */
        $groupsResult = $groups->fetchAll(PDO::FETCH_OBJ);

        return [
            'events'   => $eventsResult,
            'articles' => $articlesResult,
            'groups'   => $groupsResult,
        ];
    }

    /**
     * @return array<int, array{
     *     type: string,
     *     id: int,
     *     title: string,
     *     from: string,
     *     date: string,
     *     url: string
     * }>
     */
    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) {
            return $news;
        }
        $sql = "
            SELECT m.Id, m.Text, m.LastUpdate, m.EventId,
                   i.FirstName, i.LastName, i.NickName,
                   e.Summary, e.StartTime
            FROM Message m
            JOIN Member     mb ON mb.Id = m.PersonId
            JOIN Individual i  ON i.Id  = mb.Id
            JOIN Event      e  ON e.Id  = m.EventId
            WHERE m.LastUpdate > :searchFrom
              AND m.\"From\" = 'User'
              AND m.EventId IN (
                  SELECT IdEvent FROM Participant WHERE IdIndividual = :personId
              )
            ORDER BY m.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':searchFrom' => $searchFrom,
            ':personId'   => $connectedUser->person->id,
        ]);
        /** @var list<stdClass> $messages */
        $messages = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($messages as $message) {
            $eventId    = is_numeric($message->EventId ?? null) ? (int) $message->EventId : 0;
            $summary    = is_string($message->Summary ?? null) ? $message->Summary : '';
            $startTime  = is_string($message->StartTime ?? null) ? $message->StartTime : '';
            $text       = is_string($message->Text ?? null) ? $message->Text : '';
            $firstName  = is_string($message->FirstName ?? null) ? $message->FirstName : '';
            $lastName   = is_string($message->LastName ?? null) ? $message->LastName : '';
            $lastUpdate = is_string($message->LastUpdate ?? null) ? $message->LastUpdate : '';

            $news[] = [
                'type'  => 'message',
                'id'    => $eventId,
                'title' => $summary . '(' . TranslationManager::getShortDate($startTime) . ')' . ' => ' . $text,
                'from'  => $firstName . ' ' . $lastName,
                'date'  => $lastUpdate,
                'url'   => '/event/chat/' . $eventId,
            ];
        }
        return $news;
    }

    /**
     * @param array<int, string> $paths
     * @return array<string, bool>
     */
    public function getPathsUsedInMessages(array $paths): array
    {
        if (empty($paths)) {
            return [];
        }

        $stmt = $this->pdo->query("SELECT ImagePath FROM Message WHERE ImagePath IS NOT NULL");
        if ($stmt === false) {
            return [];
        }
        $imagePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $used = [];
        foreach ($paths as $path) {
            foreach ($imagePaths as $imagePath) {
                if (is_string($imagePath) && str_contains($imagePath, $path)) {
                    $used[$path] = true;
                    break;
                }
            }
        }
        return $used;
    }

    public function hasNewMessages(int $personId, int $lastLogId): bool
    {
        try {
            if (!$this->hasRecentMessageApiCall($lastLogId)) {
                return false;
            }
            $searchFrom = $this->getLastCreatedAt($lastLogId) ?? '';
            if (empty($searchFrom)) {
                return false;
            }
            $hasNew = count($this->doGetGroupedMessages($personId, $searchFrom)) > 0;
            if ($hasNew) {
                $_SESSION['has_new_messages'] = 1;
            }
            return $hasNew;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function updateMessage(int $messageId, int $personId, string $text): true
    {
        /** @var object{PersonId: int}|false $message */
        $message = $this->get('Message', ['Id' => $messageId], 'PersonId');
        if (!$message || $message->PersonId != $personId) {
            throw new UnauthorizedAccessException("Vous n'êtes pas autorisé à modifier ce message");
        }
        $this->set('Message', ['Text' => $text, 'LastUpdate' => date('Y-m-d H:i:s')], ['Id' => $messageId]);
        return true;
    }


    #region Private functions
    /**
     * @param array<int, stdClass> $messages
     * @return array<int, stdClass>
     */
    private function addAvatarAndTimeAgoToMessages(array $messages): array
    {
        static $gravatarHandler = null;

        if (!$gravatarHandler instanceof GravatarHandler) {
            $gravatarHandler = new GravatarHandler();
        }

        foreach ($messages as $message) {
            $message->UserImg = WebApp::computeUserImg(
                (bool) ($message->UseGravatar ?? false),
                is_string($message->Email ?? null) ? $message->Email : null,
                is_string($message->Avatar ?? null) ? $message->Avatar : null,
                $gravatarHandler
            );

            $lastUpdate = is_string($message->LastUpdate ?? null) ? $message->LastUpdate : '';
            $message->TimeAgo = MyClubDateTime::calculateTimeAgo($lastUpdate);
        }

        return $messages;
    }

    /** @return array<int, stdClass> */
    private function doGetGroupedMessages(int $personId, string $searchFrom): array
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
            SELECT m2.EventId, i.Avatar, mb.UseGravatar, i.Email
            FROM Message m2
            INNER JOIN Member     mb ON mb.Id = m2.PersonId
            INNER JOIN Individual i  ON i.Id  = mb.Id
            WHERE m2.\"From\" = 'User'
              AND m2.LastUpdate = (
                  SELECT MAX(m3.LastUpdate)
                  FROM Message m3
                  WHERE m3.EventId = m2.EventId
                    AND m3.\"From\" = 'User'
              )
        ) lp_e ON lp_e.EventId = e.Id
        WHERE et.Inactivated = 0
          AND (
              et.IdGroup IS NULL 
              OR et.IdGroup IN (
                  SELECT IdGroup 
                  FROM MemberGroup 
                  WHERE IdMember = ?
              )
          )
          $whereClause
        GROUP BY e.Id, e.Summary, lp_e.Avatar, lp_e.UseGravatar, lp_e.Email
        HAVING message_count > 0";

        $params[] = $personId;
        if ($searchFrom) {
            $params[] = $searchFrom;
        }

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
            SELECT m2.ArticleId, i.Avatar, mb.UseGravatar, i.Email
            FROM Message m2
            INNER JOIN Member     mb ON mb.Id = m2.PersonId
            INNER JOIN Individual i  ON i.Id  = mb.Id
            WHERE m2.\"From\" = 'User'
              AND m2.LastUpdate = (
                  SELECT MAX(m3.LastUpdate)
                  FROM Message m3
                  WHERE m3.ArticleId = m2.ArticleId
                    AND m3.\"From\" = 'User'
              )
        ) lp_a ON lp_a.ArticleId = a.Id
        WHERE a.PublishedBy IS NOT NULL
          AND (
              a.CreatedBy = ?
              OR a.IdGroup IS NULL
              OR a.IdGroup IN (
                  SELECT IdGroup 
                  FROM MemberGroup 
                  WHERE IdMember = ?
              )
          )
          $whereClause
        GROUP BY a.Id, a.Title, lp_a.Avatar, lp_a.UseGravatar, lp_a.Email
        HAVING message_count > 0";

        $params[] = $personId;
        $params[] = $personId;
        if ($searchFrom) {
            $params[] = $searchFrom;
        }

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
        LEFT JOIN MemberGroup mg ON mg.IdGroup = g.Id AND mg.IdMember = ?
        INNER JOIN Message m ON m.GroupId = g.Id AND m.\"From\" = 'User'
        LEFT JOIN (
            SELECT m2.GroupId, i.Avatar, mb.UseGravatar, i.Email
            FROM Message m2
            INNER JOIN Member     mb ON mb.Id = m2.PersonId
            INNER JOIN Individual i  ON i.Id  = mb.Id
            WHERE m2.\"From\" = 'User'
              AND m2.LastUpdate = (
                  SELECT MAX(m3.LastUpdate)
                  FROM Message m3
                  WHERE m3.GroupId = m2.GroupId
                    AND m3.\"From\" = 'User'
              )
        ) lp_g ON lp_g.GroupId = g.Id
        WHERE g.Inactivated = 0
          AND (g.SelfRegistration = 1 OR mg.Id IS NOT NULL)
          $whereClause
        GROUP BY g.Id, g.Name, lp_g.Avatar, lp_g.UseGravatar, lp_g.Email
        HAVING message_count > 0";

        $params[] = $personId;
        if ($searchFrom) {
            $params[] = $searchFrom;
        }

        $query = "
            $eventsQuery
            UNION ALL
            $articlesQuery
            UNION ALL
            $groupsQuery
            ORDER BY LastUpdate DESC
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        /** @var list<stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return $rows;
    }

    private function getLastCreatedAt(int $lastLogId): ?string
    {
        $stmt = $this->pdoForLog->prepare("SELECT CreatedAt FROM Log WHERE Id = ?");
        $stmt->execute([$lastLogId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (string)$result : null;
    }

    private function hasRecentMessageApiCall(int $lastLogId): bool
    {
        $stmt = $this->pdoForLog->prepare("
            SELECT COUNT(*)
            FROM Log
            WHERE Id > ?
              AND (Uri = '/api/message/add (POST)' OR Uri = '/api/message/update (POST)')
        ");
        $stmt->execute([$lastLogId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
