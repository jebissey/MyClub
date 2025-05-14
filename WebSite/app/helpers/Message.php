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
        try {
            $this->pdo->beginTransaction();
            $query = $this->pdo->prepare('INSERT INTO Message (EventId, PersonId, Text) VALUES (?, ?, ?)');
            $query->execute([$eventId, $personId, $text]);
            $messageId = $this->pdo->lastInsertId();
            $this->pdo->commit();
            
            return $messageId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateMessage($messageId, $personId, $text)
    {
        try {
            $this->pdo->beginTransaction();
            
            $query = $this->pdo->prepare('SELECT PersonId FROM Message WHERE Id = ?');
            $query->execute([$messageId]);
            $message = $query->fetch();
            if (!$message || $message['PersonId'] != $personId) {
                throw new \Exception("Vous n'êtes pas autorisé à modifier ce message");
            }
            
            $query = $this->pdo->prepare('UPDATE Message SET Text = ? WHERE Id = ?');
            $query->execute([$text, $messageId]);
            $this->pdo->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteMessage($messageId, $personId)
    {
        try {
            $this->pdo->beginTransaction();
            
            $query = $this->pdo->prepare('SELECT PersonId FROM Message WHERE Id = ?');
            $query->execute([$messageId]);
            $message = $query->fetch();
            
            if (!$message || $message['PersonId'] != $personId) {
                throw new \Exception("Vous n'êtes pas autorisé à supprimer ce message");
            }
            
            $query = $this->pdo->prepare('DELETE FROM Message WHERE Id = ?');
            $query->execute([$messageId]);
            $this->pdo->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
