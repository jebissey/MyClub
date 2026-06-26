<?php

declare(strict_types=1);

namespace app\models;

use Envms\FluentPDO\Queries\Select;
use app\helpers\Application;
use app\helpers\ConnectedUser;

class ExerciseTableDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getQuery(ConnectedUser $connectedUser): Select
    {
        $query = $this->fluent->from('exercise_list_view')
            ->select(null)
            ->select('Id, CreatedBy, Title, Detail, LastUpdate, PersonName, GroupName,  ForMembers');
        if ($connectedUser->person === null) {
            $query = $query->where('(IdGroup IS NULL AND OnlyForMembers = 0)');
        }
        $query = $query->orderBy('LastUpdate DESC');
        return $query;
    }
}
