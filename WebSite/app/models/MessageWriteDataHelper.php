<?php

declare(strict_types=1);

namespace app\models;

use app\exceptions\UnauthorizedAccessException;
use app\helpers\Application;
use app\modules\Common\valueObjects\MessageTarget;
use app\modules\Event\valueObjects\EventParticipant;

/**
 * The only writer of the Message table.
 */
final class MessageWriteDataHelper extends Data
{
    public function __construct(Application $application)
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
     * @return array<int, string> email addresses of the participants, for the BCC list
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
}
