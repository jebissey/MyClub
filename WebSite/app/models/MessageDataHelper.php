<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use stdClass;
use Throwable;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\GravatarHandler;
use app\helpers\To;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\modules\Common\interfaces\NewsProviderInterface;

/**
 * @phpstan-import-type NewsFeedItem from NewsProviderInterface
 * @phpstan-type MessageGroupItem array{
 *     type: string,
 *     Id: int,
 *     title: string,
 *     message_count: int,
 *     LastUpdate: string,
 *     UserImg: string
 * }
 */
final class MessageDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(
        Application $application,
        private readonly MessageImageDataHelper $imageDataHelper,
        private readonly MessageStatisticsDataHelper $statisticsDataHelper,
    ) {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /** @deprecated Use MessageImageDataHelper::getImageInfo() */
    /** @return array{0: int, 1: int, 2: string} */
    public function getImageInfoFromMessage(int $messageId): array
    {
        return $this->imageDataHelper->getImageInfo($messageId);
    }

    /** @deprecated Use MessageImageDataHelper::getUses() */
    /**
     * @return array{
     *     events: list<object{Id: int, Title: string, StartTime: string}>,
     *     articles: list<object{Id: int, Title: string}>,
     *     groups: list<object{Id: int, Title: string}>
     * }
     */
    public function getMessageUses(string $path): array
    {
        return $this->imageDataHelper->getUses($path);
    }

    /** @deprecated Use MessageImageDataHelper::getUsedPaths() */
    /**
     * @param array<int, string> $paths
     * @return array<string, bool>
     */
    public function getPathsUsedInMessages(array $paths): array
    {
        return $this->imageDataHelper->getUsedPaths($paths);
    }

    /** @deprecated Use MessageStatisticsDataHelper::countUserMessagesForArticle() */
    public function countUserMessagesForArticle(int $articleId): int
    {
        return $this->statisticsDataHelper->countUserMessagesForArticle($articleId);
    }

    /** @deprecated Use MessageStatisticsDataHelper::countByEmailForSeason() */
    /**
     * @param array{start: string, end: string} $season
     * @return array<string, int>
     */
    public function getMessages(array $season): array
    {
        return $this->statisticsDataHelper->countByEmailForSeason($season);
    }

    /** @return list<MessageGroupItem> */
    public function getGroupedMessages(int $personId, string $searchFrom, GravatarHandler $gravatarHandler): array
    {
        $rows = $this->doGetGroupedMessages($personId, $searchFrom);

        return array_values(array_map(
            static function (stdClass $row) use ($gravatarHandler): array {
                $userImg = WebApp::computeUserImg(
                    To::bool($row->UseGravatar ?? null),
                    is_string($row->Email ?? null) ? $row->Email : null,
                    is_string($row->Avatar ?? null) ? $row->Avatar : null,
                    $gravatarHandler
                );

                return [
                    'type'          => To::str($row->type ?? ''),
                    'Id'            => To::int($row->Id ?? 0),
                    'title'         => To::str($row->title ?? ''),
                    'message_count' => To::int($row->message_count ?? 0),
                    'LastUpdate'    => To::str($row->LastUpdate ?? ''),
                    'UserImg'       => $userImg,
                ];
            },
            $rows
        ));
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
            $eventId    = To::int($message->EventId ?? null);
            $summary    = To::str($message->Summary ?? null);
            $startTime  = To::str($message->StartTime ?? null);
            $text       = To::str($message->Text ?? null);
            $firstName  = To::str($message->FirstName ?? null);
            $lastName   = To::str($message->LastName ?? null);
            $lastUpdate = To::str($message->LastUpdate ?? null);

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

    #region Private functions
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
        return $result !== false ? To::requireStr($result) : null;
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
        return To::requireInt($stmt->fetchColumn()) > 0;
    }
}
