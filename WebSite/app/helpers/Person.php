<?php

namespace app\helpers;

use DateTime;
use PDO;

class Person
{
    private PDO $pdo;
    private $fluent;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
    }


    public function getPersons($idGroup)
    {
        $innerJoin = $and = '';
        if (!empty($idGroup)) {
            $innerJoin = 'INNER JOIN PersonGroup on PersonGroup.IdPerson = Person.Id';
            $and = 'AND PersonGroup.IdGroup = ' . $idGroup;
        }
        $query = $this->pdo->query("
            SELECT Email, Preferences, Availabilities, Person.Id
            FROM Person
            $innerJoin
            WHERE Person.Inactivated = 0 $and
        ");
        return $query->fetchAll();
    }


    public function getActivePerson()
    {
        return $this->fluent->from('Person')
            ->select('Id, FirstName, LastName, Email, Avatar')
            ->where('Inactivated = 0')
            ->fetchAll();
    }

}