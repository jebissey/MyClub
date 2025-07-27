<?php

namespace app\helpers;

use Exception;

class MessageDataHelper extends Data
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addMessage($eventId, $personId, $text)
    {
        $messageId = $this->fluent->insertInto(
            'Message',
            [
                'EventId'  => $eventId,
                'PersonId' => $personId,
                'Text'     => $text,
                'From'     => 'User'
            ]
        )->execute();
        return $messageId;
    }

    public function addWebAppMessages($eventId, $participants, $text): array
    {
        $bccList = [];
        foreach ($participants as $participant) {
            $bccList[] = $participant->Email;
            $this->fluent->insertInto('Message')
                ->values([
                    'EventId' => $eventId,
                    'PersonId' => $participant->Id,
                    'Text' => $text,
                    '"From"' => 'Webapp'
                ])
                ->execute();
        }
        return $bccList;
    }

    public function deleteMessage($messageId, $personId)
    {
        $message = $this->fluent->from('Message')->select('PersonId')->where('Id', $messageId)->fetch();
        if (!$message || $message->PersonId != $personId) {
            throw new Exception("Vous n'êtes pas autorisé à supprimer ce message");
        }
        $this->fluent->deleteFrom('Message')->where('Id', $messageId)->execute();
        return true;
    }

    public function getEventMessages($eventId)
    {
        return $this->fluent
            ->from('Message')
            ->select('Message.*, Person.FirstName, Person.LastName, Person.NickName, Person.Avatar, Person.UseGravatar')
            ->leftJoin('Person ON Message.PersonId = Person.Id')
            ->where('EventId', $eventId)
            ->orderBy('Message.Id ASC')
            ->fetchAll();
    }

    public function updateMessage($messageId, $personId, $text)
    {
        $message = $this->fluent->from('Message')->select('PersonId')->where('Id', $messageId)->fetch();
        if (!$message || $message->PersonId != $personId) {
            throw new Exception("Vous n'êtes pas autorisé à modifier ce message");
        }
        $this->fluent->update('Message')->set(['Text' => $text, 'LastUpdate' =>  date('Y-m-d H:i:s')])->where('Id', $messageId)->execute();
        return true;
    }

    public function getMessageNews($person, $searchFrom)
    {
        $sql = "
            SELECT m.Id, m.Text, m.LastUpdate, m.EventId, p.FirstName, p.LastName, p.NickName, e.Summary, e.StartTime
            From Message m
            JOIN Person p ON p.Id = m.PersonId
            JOIN Event e ON e.Id = m.EventId
            WHERE m.LastUpdate > :searchFrom AND m.'From' = 'User' 
            AND m.EventId IN (SELECT IdEvent FROM Participant WHERE IdPerson = $person->Id)
            ORDER BY m.LastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':searchFrom' => $searchFrom
        ]);
        $messages = $stmt->fetchAll();
        $news = [];
        foreach ($messages as $message) {
            $news[] = [
                'type' => 'message',
                'id' => $message->EventId,
                'title' => $message->Text,
                'from' => $message->FirstName . ' ' . $message->LastName,
                'date' => $message->LastUpdate,
                'url' => '/event/chat/' . $message->EventId
            ];
        }
        return $news;
    }
}
