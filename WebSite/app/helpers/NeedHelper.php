<?php

namespace app\helpers;


class NeedHelper extends Data
{
    public function getNeedsAndTheirTypes()
    {
        return $this->fluent
            ->from('Need')
            ->select('Need.*, NeedType.Name AS TypeName')
            ->leftJoin('NeedType ON Need.IdNeedType = NeedType.Id')
            ->orderBy('NeedType.Name, Need.Name')
            ->fetchAll();
    }
}
