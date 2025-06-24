<?php

namespace app\helpers;

use PDO;

class Message
{
    private $pdo;
    private $fluent;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
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

    public function addMessage($eventId, $personId, $text)
    {
        $messageId = $this->fluent->insertInto(
            'Message',
            [
                'EventId'  => $eventId,
                'PersonId' => $personId,
                'Text'     => $text
            ]
        )->execute();
        return $messageId;
    }

    public function updateMessage($messageId, $personId, $text)
    {
        $message = $this->fluent->from('Message')->select('PersonId')->where('Id', $messageId)->fetch();
        if (!$message || $message->PersonId != $personId) {
            throw new \Exception("Vous n'êtes pas autorisé à modifier ce message");
        }
        $this->fluent->update('Message')->set(['Text' => $text, 'LastUpdate' =>  date('Y-m-d H:i:s')])->where('Id', $messageId)->execute();
        return true;
    }

    public function deleteMessage($messageId, $personId)
    {
        $message = $this->fluent->from('Message')->select('PersonId')->where('Id', $messageId)->fetch();
        if (!$message || $message->PersonId != $personId) {
            throw new \Exception("Vous n'êtes pas autorisé à supprimer ce message");
        }
        $this->fluent->deleteFrom('Message')->where('Id', $messageId)->execute();
        return true;
    }
}
