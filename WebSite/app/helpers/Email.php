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

    public function getEmailsOfInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay)
    {
        $persons = $this->getInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
        $filteredEmails = [];
        foreach ($persons as $person) {
            $filteredEmails[] = $person->Email;
        }
        return $filteredEmails;
    }

    public function getInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay)
    {
        $persons = $this->getPersons($idGroup);
        $filteredPeople = [];
        foreach ($persons as $person) {
            $include = true;

            if ($person->Preferences != '') {
                $preferences = json_decode($person->Preferences, true);
                if (isset($preferences['noAlerts']) && $preferences['noAlerts'] == 'on') {
                    $include = false;
                    continue;
                }
            }

            if (!empty($idEventType)) {
                if ($person->Preferences != '') {
                    $preferences = json_decode($person->Preferences, true);
                    if ($preferences != '' && (!isset($preferences['eventTypes'][$idEventType]))) {
                        $include = false;
                        continue;
                    }
                }
            }

            if ($dayOfWeek != '' && $timeOfDay != '') {
                if ($person->Availabilities != '') {
                    $availabilities = json_decode($person->Availabilities, true);
                    if (isset($availabilities[$dayOfWeek][$timeOfDay]) != 'on') {
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

    public static function send($emailFrom, $emailTo, $subject, $body)
    {
        $headers = array(
            'From' => $emailFrom,
            'Reply-To' => $emailFrom,
            'Return-Path' => $emailTo,
            'X-Mailer' => 'PHP/' . phpversion(),
            'Content-Type' => 'text/plain; charset=UTF-8'
        );
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= $key . ': ' . $value . "\r\n";
        }
        return mail($emailTo, $subject, $body, $headerString);
    }
}
