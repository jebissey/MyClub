<?php

namespace app\helpers;

use PDO;

class Email
{
    private PDO $pdo;
    private $fluent;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
    }

    public function getEmailsOfInterestedPeople($idGroup, $dayOfWeek, $timeOfDay)
    {
        $persons = $this->getInterestedPeople($idGroup, $dayOfWeek, $timeOfDay);
        $filteredEmails = [];
        foreach ($persons as $person) {
            $filteredEmails[] = $person->Email;
        }
        return $filteredEmails;
    }

    public function getInterestedPeople($idGroup, $dayOfWeek, $timeOfDay)
    {
        $persons = $this->getPersons($idGroup);
        $filteredPeople = [];
        foreach ($persons as $person) {
            $include = true;
            if (!empty($idEventType)) {
                if ($person->Preferences != '') {
                    $preferences = json_decode($person->Preferences, true);
                    if ($preferences != '' && !isset($preferences['eventTypes'][$idEventType])) {
                        $include = false;
                    }
                }
            }
            if ($dayOfWeek != '' && $timeOfDay != '') {
                if ($person->Availabilities != '') {
                    $availabilities = json_decode($person->Availabilities, true);
                    if (isset($availabilities[$dayOfWeek][$timeOfDay])) {
                        $include = false;
                    }
                }
            }
            if ($include) {
                $filteredPeople[] = $person;
            }
        }
        return $filteredPeople;
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
}
