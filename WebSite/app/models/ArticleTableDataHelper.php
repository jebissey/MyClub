<?php

declare(strict_types=1);

namespace app\models;

use \Envms\FluentPDO\Queries\Select;

use app\helpers\Application;
use app\helpers\ConnectedUser;

class ArticleTableDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getQuery(ConnectedUser $connectedUser, int $spotlightArticleId): Select
    {
        $query = $this->fluent->from('article_list_view')
            ->select(null)
            ->select('Id, CreatedBy, Title, LastUpdate, PersonName, GroupName, Pool, PoolDetail, ForMembers, Messages, Menu')
            ->select('CASE 
                WHEN PublishedBy IS NULL THEN "non" 
                ELSE 
                    CASE 
                        WHEN Id = ' . $spotlightArticleId . ' THEN "oui 📌"
                        ELSE Published
                    END
                END AS Published');

        if ($connectedUser->person ?? false) {
            if (!$connectedUser->isEditor()) {
                $query = $query->where('(CreatedBy = ' . $connectedUser->person->Id . '
                OR (PublishedBy IS NOT NULL 
                    AND (IdGroup IS NULL OR IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ' . $connectedUser->person->Id . '))
                ))');
            }
        } else $query = $query->where('(IdGroup IS NULL AND OnlyForMembers = 0 AND PublishedBy IS NOT NULL)');

        $query = $query->orderBy('LastUpdate DESC');
        return $query;
    }
}
