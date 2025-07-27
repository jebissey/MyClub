<?php

namespace app\helpers;

class EventNeedHelper extends Data
{
    public function __construct()
    {
        parent::__construct();
    }

    public function needsForEvent($eventId)
    {
        return $this->fluent->from('EventNeed')
            ->select('EventNeed.*, Need.Label, Need.Name, Need.ParticipantDependent, NeedType.Name as TypeName')
            ->join('Need ON EventNeed.IdNeed = Need.Id')
            ->join('NeedType ON Need.IdNeedType = NeedType.Id')
            ->where('EventNeed.IdEvent', $eventId)
            ->fetchAll();
    }
}
