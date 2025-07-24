<?php

namespace app\helpers;


class MessagePersonHelper extends Data
{
    public function getEventMessages($eventId)
    {
        return $this->fluent->from('Message')
            ->select('Message.*, Person.FirstName, Person.LastName, Person.NickName, Person.Avatar, Person.UseGravatar, Person.Email')
            ->join('Person ON Person.Id = Message.PersonId')
            ->where('EventId', $eventId)
            ->where('Message."From" = "User"')
            ->orderBy('Message.Id ASC')
            ->fetchAll();
    }
}
