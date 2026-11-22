<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;
use app\modules\Common\valueObjects\MessageTarget;
use app\modules\Common\valueObjects\MessageTargetInfo;
use app\modules\Event\valueObjects\EventCreatorSummaryRow;

final class MessageTargetDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    public function find(MessageTarget $target): ?MessageTargetInfo
    {
        return match (true) {
            $target->articleId !== null => $this->findArticle($target->articleId),
            $target->eventId !== null   => $this->findEvent($target->eventId),
            $target->groupId !== null   => $this->findGroup($target->groupId),
            default                     => null,
        };
    }

    private function findArticle(int $id): ?MessageTargetInfo
    {
        $row = $this->get('Article', ['Id' => $id], 'CreatedBy, Title');
        if ($row === false) {
            return null;
        }
        /** @var object{CreatedBy: int|string, Title: string} $row */
        return new MessageTargetInfo('article', $id, $row->Title, articleAuthorId: (int) $row->CreatedBy);
    }

    private function findEvent(int $id): ?MessageTargetInfo
    {
        $row = $this->get('Event', ['Id' => $id], 'CreatedBy, Summary');
        if ($row === false) {
            return null;
        }
        /** @var object{CreatedBy: int|string, Summary: string} $row */
        $event = EventCreatorSummaryRow::fromStdClass($row);

        return new MessageTargetInfo('event', $id, $event->summary, eventCreatorId: $event->createdBy);
    }

    private function findGroup(int $id): ?MessageTargetInfo
    {
        $row = $this->get('Group', ['Id' => $id], 'Name');
        if ($row === false) {
            return null;
        }
        /** @var object{Name: string} $row */
        return new MessageTargetInfo('group', $id, $row->Name);
    }
}
