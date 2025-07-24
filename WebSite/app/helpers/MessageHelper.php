<?php

namespace app\helpers;

class MessageHelper extends Data
{
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
            throw new \Exception("Vous n'êtes pas autorisé à supprimer ce message");
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
            throw new \Exception("Vous n'êtes pas autorisé à modifier ce message");
        }
        $this->fluent->update('Message')->set(['Text' => $text, 'LastUpdate' =>  date('Y-m-d H:i:s')])->where('Id', $messageId)->execute();
        return true;
    }
}
