<?php

namespace app\helpers;

class ParticipantDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getEventParticipants($eventId)
    {
        return $this->fluent->from('Participant pa')
            ->leftJoin('Person pe ON pa.IdPerson = pe.Id')
            ->leftJoin('Contact c ON pa.IdContact = c.Id')
            ->where('pa.IdEvent', $eventId)
            ->select('
                COALESCE(pe.Email, c.Email) AS Email,
                COALESCE(pe.NickName, c.NickName) AS NickName,
                pe.FirstName, pe.LastName, pe.Id AS PersonId, c.Id AS ContactId
            ')
            ->orderBy('pe.FirstName, pe.LastName, c.NickName')
            ->fetchAll();
    }
}
