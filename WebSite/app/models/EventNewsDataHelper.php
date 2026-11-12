<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use stdClass;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\modules\Common\interfaces\NewsProviderInterface;

final class EventNewsDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /** @return array<int, array{type: string, id: int, title: string, date: string, url: string}> */
    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if ($connectedUser->person === null) {
            return $news;
        }
        $sql = "
            SELECT e.Id, e.Summary, e.LastUpdate
            FROM Event e
            JOIN EventType et ON e.IdEventType = et.Id
            LEFT JOIN MemberGroup mg 
                ON et.IdGroup = mg.IdGroup 
               AND mg.IdMember = :personId
            WHERE e.LastUpdate >= :searchFrom
              AND (
                  et.IdGroup IS NULL
                  OR mg.IdMember IS NOT NULL
              )
            ORDER BY e.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':personId'   => $connectedUser->person->id,
            ':searchFrom' => $searchFrom
        ]);
        /** @var array<int, stdClass> $events */
        $events = $stmt->fetchAll(PDO::FETCH_OBJ);
        foreach ($events as $event) {
            $news[] = [
                'type'  => 'event',
                'id'    => To::int($event->Id),
                'title' => To::str($event->Summary),
                'date'  => To::str($event->LastUpdate),
                'url'   => '/event/' . To::int($event->Id),
            ];
        }
        return $news;
    }
}