<?php
declare(strict_types=1);

namespace app\models;

use app\exceptions\UnauthorizedAccessException;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\TranslationManager;
use app\interfaces\NewsProviderInterface;

class MessageDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function addMessage(int $eventId, int $personId, string $text): int|false
    {
        $messageId = $this->set('Message', [
            'EventId'  => $eventId,
            'PersonId' => $personId,
            'Text'     => $text,
            '"From"'     => 'User'
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
                'PersonId' => $participant->Id,
                'Text' => $text,
                '"From"' => 'Webapp'
            ]);
        }
        return $bccList;
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
                Person.UseGravatar
            FROM Message
            LEFT JOIN Person ON Message.PersonId = Person.Id
            WHERE Message.EventId = :eventId
            ORDER BY Message.Id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eventId' => $eventId]);
        return $stmt->fetchAll();
    }

    public function updateMessage(int $messageId, int $personId, string $text): true
    {
        $message = $this->get('Message', ['Id', $messageId], 'PersonId');
        if (!$message || $message->PersonId != $personId) {
            throw new UnauthorizedAccessException("Vous n'êtes pas autorisé à modifier ce message");
        }
        $this->set('Message', ['Text' => $text, 'LastUpdate' =>  date('Y-m-d H:i:s')], ['Id', $messageId]);
        return true;
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
}
