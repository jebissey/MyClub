<?php

declare(strict_types=1);

namespace app\models;

use app\helpers\Application;

class NeedTypeDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function insertOrUpdate(?int $id, string $name): int
    {
        if ($id === null) {
            $this->set('NeedType', ['Name' => $name], ['Id' => $id]);
        } else {
            $id = $this->set('NeedType', ['Name' => $name]);
        }
        return  $id;
    }
}
