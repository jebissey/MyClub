<?php

namespace app\helpers;

class TableControllerHelper extends Data
{
    public function getEventTypesQuery()
    {
        return $this->fluent->from('EventType')
            ->select('EventType.Id AS EventTypeId, EventType.Name AS EventTypeName, `Group`.Name AS GroupName')
            ->select('GROUP_CONCAT(Attribute.Name, ", ") AS Attributes')
            ->leftJoin('`Group` ON EventType.IdGroup = `Group`.Id')
            ->leftJoin('EventTypeAttribute ON EventType.Id = EventTypeAttribute.IdEventType')
            ->leftJoin('Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id')
            ->where('EventType.Inactivated', 0)
            ->groupBy('EventType.Id')
            ->orderBy('EventType.Name');
    }

    public function getPersonsQuery()
    {
        return $this->fluent->from('Person')
            ->select('Id, FirstName, LastName, NickName, Email')
            ->orderBy('LastName')
            ->where('Inactivated = 0');
    }
}
