<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use stdClass;
use app\helpers\Application;
use app\helpers\ChatMessageMapper;
use app\modules\Common\valueObjects\ChatMessage;

/**
 * Reads the chat messages attached to an article, an event or a group.
 */
final class ChatMessageDataHelper extends Data
{
    public function __construct(Application $application, private readonly ChatMessageMapper $mapper)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /** @return list<ChatMessage> */
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

        /** @var list<stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return $this->mapper->mapAll($rows);
    }

    /** @return list<ChatMessage> */
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
            WHERE Message.EventId = :eventId
              AND Message.\"From\" = 'User'
            ORDER BY Message.Id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);

        /** @var list<stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return $this->mapper->mapAll($rows);
    }

    /** @return list<ChatMessage> */
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

        /** @var list<stdClass> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return $this->mapper->mapAll($rows);
    }
}
