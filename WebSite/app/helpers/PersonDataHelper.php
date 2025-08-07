<?php

namespace app\helpers;

use DateTime;

use app\interfaces\NewsProviderInterface;

class PersonDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getPersonsInGroup(?int $idGroup): array
    {
        $innerJoin = $and = '';
        if ($idGroup !== null) {
            $innerJoin = 'INNER JOIN PersonGroup on PersonGroup.IdPerson = Person.Id';
            $and = 'AND PersonGroup.IdGroup = ' . $idGroup;
        }
        return $this->pdo->query("
            SELECT Person.Id, FirstName, LastName, Email, Preferences, Availabilities
            FROM Person
            $innerJoin
            WHERE Person.Inactivated = 0 $and
            ORDER BY FirstName, LastName
        ")->fetchAll();
    }

    public function getPersonsInGroupForDirectory($groupId)
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.* 
            FROM Person p
            JOIN PersonGroup pg ON p.Id = pg.IdPerson
            WHERE pg.IdGroup = ? AND p.InPresentationDirectory = 1 AND p.Inactivated = 0
            ORDER BY p.LastName, p.FirstName
        ");
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    public function getPublisher($id): string|null
    {
        if ($id == null) return null;
        $person = $this->get('Person', ['Id' => $id], 'FirstName, LastName');
        return "publié par " . $person->FirstName . " " . $person->LastName;
    }

    public function create()
    {
        $query = $this->pdo->prepare("SELECT Id FROM Person WHERE Email = ''");
        $query->execute();
        $id = $query->fetch()->Id ?? null;
        if ($id == null) {
            $query = $this->pdo->prepare("
                INSERT INTO Person (Email, FirstName, LastName, Imported) 
                VALUES ('', '', '', 0)
            ");
            $query->execute([]);
            $id = $this->pdo->lastInsertId();
        }
        return $id;
    }

    public function setPassword($password, $personId)
    {
        $stmt = $this->pdo->prepare('UPDATE Person SET Password = ?, Token = null, TokenCreatedAt = null WHERE Id = ?');
        $stmt->execute($password, $personId);
    }

    public function setToken($personId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenCreatedAt = (new DateTime())->format('Y-m-d H:i:s');
        $query = $this->pdo->prepare('UPDATE Person SET Token = ?, TokenCreatedAt = ? WHERE Id = ?');
        $query->execute([$token, $tokenCreatedAt, $personId]);
        return $token;
    }

    public function updateActivity($email)
    {
        $lastActivity = $this->fluentForLog->from('Log')
            ->select(null)
            ->select('CreatedAt')
            ->where('Who COLLATE NOCASE', $email)
            ->orderBy('Id DESC')
            ->limit(1)
            ->fetch('CreatedAt');
        if ($lastActivity) $this->fluent->update('Person')->set('LastSignOut', $lastActivity)->where('Email COLLATE NOCASE', $email)->execute();
        $this->fluent->update('Person')->set(['LastSignIn' => date('Y-m-d H:i:s')])->where('Email COLLATE NOCASE', $email)->execute();
    }

    public function getWebmasterEmail()
    {
        $query = $this->pdo->query(
            '
            SELECT Email FROM Person
            INNER JOIN PersonGroup on Person.Id = PersonGroup.IdPerson
            INNER JOIN "Group" on "Group".Id = PersonGroup.IdGroup
            INNER JOIN GroupAuthorization on "Group".Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id
            WHERE Authorization.Name = "Webmaster";'
        );
        return $query->fetchColumn();
    }

    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) return $news;
        $sql = "
            SELECT Id, Email, FirstName, LastName, PresentationLastUpdate
            FROM Person
            WHERE InPresentationDirectory = 1
            AND PresentationLastUpdate >= :searchFrom
            AND Email != :email
            ORDER BY PresentationLastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':searchFrom' => $searchFrom,
            ':email' => $connectedUser->person->Email
        ]);
        $presentations = $stmt->fetchAll();
        foreach ($presentations as $presentation) {
            $fullName = trim($presentation->FirstName . ' ' . $presentation->LastName);
            if (empty($fullName)) $fullName = $presentation->Email;
            $news[] = [
                'type' => 'presentation',
                'id' => $presentation->Id,
                'title' => 'Présentation de ' . $fullName,
                'date' => $presentation->PresentationLastUpdate,
                'url' => '/presentation/' . $presentation->Id
            ];
        }
        return $news;
    }

    public function getPersonWantedToBeAlerted($idArticle): array
    {
        $idGroup = $this->get('Article', ['Id' => $idArticle], 'IdGroup')->IdGroup;
        $idSurvey = $this->get('Survey', ['IdArticle' => $idArticle], 'Id')->Id;
        $persons = (new PersonDataHelper($this->application))->getPersonsInGroup($idGroup);
        $filteredEmails = [];
        foreach ($persons as $person) {
            $include = false;
            if ($person->Preferences ?? '' != '') {
                $preferences = json_decode($person->Preferences ?? '', true);
                if ($preferences != '' && isset($preferences['eventTypes']['newArticle'])) {
                    if (isset($preferences['eventTypes']['newArticle']['pollOnly'])) {
                        if ($idSurvey) $include = true;
                    } else $include = true;
                }
            }
            if ($include) {
                $filteredEmails[] = $person->Email;
                $this->set('Message', [
                    'EventId' => null,
                    'PersonId' => $person->Id,
                    'Text' =>  "New article \n\n /articles/" . $idArticle,
                    '"From"' => 'Webapp'
                ]);
            }
        }
        return $filteredEmails;
    }

    public function getEmailsOfInterestedPeople(?int $idGroup, ?int $idEventType, string $dayOfWeek, string $timeOfDay): array
    {
        $persons = $this->getInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
        $filteredEmails = [];
        foreach ($persons as $person) {
            $filteredEmails[] = $person->Email;
        }
        return $filteredEmails;
    }

    public function getInterestedPeople(?int $idGroup, ?int $idEventType, string $dayOfWeek, string $timeOfDay): array
    {
        $persons = (new PersonDataHelper($this->application))->getPersonsInGroup($idGroup);
        $filteredPeople = [];
        foreach ($persons as $person) {
            if ((new PersonPreferences())->isPersonInterested($person, $idEventType, $dayOfWeek, $timeOfDay)) $filteredPeople[] = $person;
        }
        return $filteredPeople;
    }

    public function sendRegistrationLink($adminEmail, $name, $emailContact, $event): bool
    {
        $contact = $this->fluent->from('Contact')->where('Email', $emailContact)->fetch();
        if (!$contact) {
            $contactData = [
                'Email' => $emailContact,
                'NickName' => $name,
                'Token' => bin2hex(random_bytes(32)),
                'TokenCreatedAt' => date('Y-m-d H:i:s')
            ];
            $contactId = $this->fluent->insertInto('Contact')->values($contactData)->execute();
        } else {
            $token = bin2hex(random_bytes(32));
            $this->fluent->update('Contact')
                ->set([
                    'Token' => $token,
                    'TokenCreatedAt' => date('Y-m-d H:i:s')
                ])
                ->where('Id', $contact->Id)
                ->execute();
            $contact->Token = $token;
        }
        if (!$contact) {
            $contact = $this->fluent->from('Contact')->where('Id', $contactId)->fetch();
        }
        $registrationLink = Webapp::getBaseUrl() . "events/{$event->Id}/{$contact->Token}";
        $subject = "Lien d'inscription pour " . $event->Summary;
        $body = $registrationLink;
        return Email::send($adminEmail, $emailContact, $subject, $body);
    }
}
