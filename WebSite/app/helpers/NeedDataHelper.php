<?php

namespace app\helpers;


class NeedDataHelper extends Data
{
    public function __construct()
    {
        parent::__construct();
    }

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
