<?php

namespace app\helpers;

use InvalidArgumentException;
use PDO;

class Email
{
    private PDO $pdo;
    private $fluent;
    private Person $person;
    private PersonPreferences $personPreferences;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fluent = new \Envms\FluentPDO\Query($pdo);
        $this->person = new Person($pdo);
        $this->personPreferences = new PersonPreferences($pdo);
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
        $persons = $this->person->getPersons($idGroup);
        $filteredPeople = [];
        foreach ($persons as $person) {
            if ($this->personPreferences->isPersonInterested($person, $idEventType, $dayOfWeek, $timeOfDay)) {
                $filteredPeople[] = $person;
            }
        }
        return $filteredPeople;
    }



    public static function send($emailFrom, $emailTo, $subject, $body, $cc = null, $bcc = null, $isHtml = false)
    {
        if (!filter_var($emailFrom, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Adresse expéditeur invalide : $emailFrom");
        }
        if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Adresse destinataire invalide : $emailTo");
        }
        $headers = array(
            'From' => $emailFrom,
            'Reply-To' => $emailFrom,
            'Return-Path' => $emailFrom,
            'X-Mailer' => 'PHP/' . phpversion(),
            'Content-Type' => $isHtml ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8'
        );
        if ($cc) {
            $ccList = is_array($cc) ? $cc : explode(',', $cc);
            $ccList = array_filter(array_map('trim', $ccList), function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });
            if ($ccList) {
                $headers['Cc'] = implode(', ', $ccList);
            }
        }
        if ($bcc) {
            $bccList = is_array($bcc) ? $bcc : explode(',', $bcc);
            $bccList = array_filter(array_map('trim', $bccList), function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });
            if ($bccList) {
                $headers['Bcc'] = implode(', ', $bccList);
            }
        }
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= $key . ': ' . $value . "\r\n";
        }
        return mail($emailTo, $subject, $body, $headerString);
    }
}
