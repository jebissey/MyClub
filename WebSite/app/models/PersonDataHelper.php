<?php

declare(strict_types=1);

namespace app\models;

use PDO;

use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\GravatarHandler;
use app\helpers\PersonPreferences;
use app\helpers\WebApp;
use app\interfaces\NewsProviderInterface;
use app\modules\Common\services\EmailService;

class PersonDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(
        Application $application,
        private PersonPreferences $personPreferences,
        private EmailService $emailService
    ) {
        parent::__construct($application);
    }

    public function create(): int
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
        return (int)$id;
    }

    public function getEmailsOfInterestedPeople(?int $idGroup, ?int $idEventType, ?int $dayOfWeek, string $timeOfDay): array
    {
        $persons = $this->getInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
        $filteredEmails = [];
        foreach ($persons as $person) {
            $filteredEmails[] = $person->Email;
        }
        return $filteredEmails;
    }

    public function getInterestedPeople(?int $idGroup, ?int $idEventType, ?int $dayOfWeek, string $timeOfDay): array
    {
        $persons = $this->getPersonsInGroup($idGroup);
        $filteredPeople = [];
        foreach ($persons as $person) {
            if ($this->personPreferences->isPersonInterested($person, $idEventType, $dayOfWeek, $timeOfDay)) $filteredPeople[] = $person;
        }
        return $filteredPeople;
    }

    public function getMembersAlerts(): array
    {
        $query = "
            SELECT 
                p.FirstName || ' ' || p.LastName || 
                CASE 
                    WHEN p.NickName IS NOT NULL AND p.NickName != '' THEN ' (' || p.NickName || ')'
                    ELSE ''
                END AS clubMember,
                CASE 
                    WHEN p.Preferences LIKE '%noAlerts%' THEN 'X'
                    ELSE ''
                END AS NoAlert,
                CASE 
                    WHEN p.Preferences LIKE '%newEvent%' THEN 'X'
                    ELSE ''
                END AS NewEvent,
                CASE 
                    WHEN p.Preferences LIKE '%newArticle%' THEN 'X'
                    ELSE ''
                END AS NewArticle
            FROM Person AS p
            WHERE (p.Preferences LIKE '%noAlerts%' 
            OR p.Preferences LIKE '%newEvent%' 
            OR p.Preferences LIKE '%newArticle%')
            AND p.Inactivated = 0
            ORDER BY clubMember
        ";
        $stmt = $this->pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
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
        $presentations = $stmt->fetchAll(PDO::FETCH_OBJ);
        foreach ($presentations as $presentation) {
            $fullName = trim($presentation->FirstName . ' ' . $presentation->LastName);
            if (empty($fullName)) $fullName = $presentation->Email;
            $news[] = [
                'type' => 'presentation',
                'id' => $presentation->Id,
                'title' => 'Présentation de ' . $fullName,
                'date' => $presentation->PresentationLastUpdate,
                'url' => '/user/presentation/' . $presentation->Id
            ];
        }
        return $news;
    }

    public function getPersonsInGroup(?int $idGroup): array
    {
        $innerJoin = $and = '';
        if ($idGroup !== null) {
            $innerJoin = 'INNER JOIN PersonGroup on PersonGroup.IdPerson = Person.Id';
            $and = 'AND PersonGroup.IdGroup = ' . $idGroup;
        }
        return $this->pdo->query("
            SELECT Person.Id as PersonId, FirstName, LastName, Email, Preferences, Availabilities
            FROM Person
            $innerJoin
            WHERE Person.Inactivated = 0 $and
            ORDER BY FirstName, LastName
        ")->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPersonsInGroupForDirectory(int $groupId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.* 
            FROM Person p
            JOIN PersonGroup pg ON p.Id = pg.IdPerson
            WHERE pg.IdGroup = ? AND p.InPresentationDirectory = 1 AND p.Inactivated = 0
            ORDER BY p.FirstName, p.LastName
        ");
        $stmt->execute([$groupId]);
        $persons = $stmt->fetchAll(PDO::FETCH_OBJ);

        $gravatarHandler = new GravatarHandler();
        foreach ($persons as $person) {
            $person->UserImg = WebApp::getUserImg($person, $gravatarHandler);
        }
        return $persons;
    }

    public function getPersonWantedToBeAlerted(int $idArticle): array
    {
        $idGroup = null;
        if ($group = $this->get('Article', ['Id' => $idArticle], 'IdGroup')) {
            $idGroup = $group->IdGroup;
        }
        $idSurvey = null;
        if ($survey = $this->get('Survey', ['IdArticle' => $idArticle], 'Id')) {
            $idSurvey = $survey->Id;
        }
        $idOrder = null;
        if ($order = $this->get('Order', ['IdArticle' => $idArticle], 'Id')) {
            $idOrder = $order->Id;
        }
        $persons = $this->getPersonsInGroup($idGroup);
        $filteredEmails = [];
        foreach ($persons as $person) {
            if (empty($person->Preferences)) {
                continue;
            }
            $preferences = json_decode($person->Preferences, true);
            if (!$preferences || empty($preferences['eventTypes']['newArticle']['enabled'])) {
                continue;
            }
            $articlePrefs = $preferences['eventTypes']['newArticle'];
            $include = false;

            if (isset($articlePrefs['pollOnly'])) {
                $include = (bool) $idSurvey;
            } elseif (isset($articlePrefs['orderOnly'])) {
                $include = (bool) $idOrder;
            } elseif (isset($articlePrefs['poll_or_order'])) {
                $include = ($idSurvey || $idOrder);
            } else {
                $include = true;
            }

            if ($include) {
                $filteredEmails[] = $person->Email;
                $this->set('Message', [
                    'EventId'  => null,
                    'PersonId' => $person->PersonId,
                    'Text'    => "Nouvel article publié\n\n/article/" . $idArticle,
                    'From'    => 'Webapp'
                ]);
            }
        }
        return $filteredEmails;
    }

    public function getPublisher(?int $idPerson): string|null
    {
        if ($idPerson === null) return null;
        $person = $this->get('Person', ['Id' => $idPerson], 'FirstName, LastName');
        return "publié par " . $person->FirstName . " " . $person->LastName;
    }

    public function getWebmasterEmail(): string
    {
        $query = $this->pdo->query(
            '
            SELECT Email FROM Person
            INNER JOIN PersonGroup on Person.Id = PersonGroup.IdPerson
            INNER JOIN "Group" on "Group".Id = PersonGroup.IdGroup
            INNER JOIN GroupAuthorization on "Group".Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization on GroupAuthorization.IdAuthorization = Authorization.Id
            WHERE Authorization.Name = "Webmaster"'
        );
        return $query->fetchColumn();
    }

    public function sendRegistrationLink($adminEmail, $name, $emailContact, $event): bool
    {
        $contact = $this->get('Contact', ['Email' => $emailContact]);
        if (!$contact) {
            $contactData = [
                'Email' => $emailContact,
                'NickName' => $name,
                'Token' => bin2hex(random_bytes(32)),
                'TokenCreatedAt' => date('Y-m-d H:i:s')
            ];
            $contactId = $this->set('Contact', $contactData);
        } else {
            $token = bin2hex(random_bytes(32));
            $this->set(
                'Contact',
                [
                    'Token' => $token,
                    'TokenCreatedAt' => date('Y-m-d H:i:s')
                ],
                ['Id' => $contact->Id]
            );
            $contact->Token = $token;
        }
        if (!$contact) $contact = $this->get('Contact', ['Id' => $contactId]);
        $registrationLink = Webapp::getBaseUrl() . "event/{$event->Id}/{$contact->Token}";
        $subject = "Lien d'inscription pour " . $event->Summary;
        $body = $registrationLink;
        return $this->emailService->send($adminEmail, $emailContact, $subject, $body);
    }

    public function updateActivity(string $email): void
    {
        $stmt = $this->pdoForLog->prepare("
            SELECT CreatedAt 
            FROM Log 
            WHERE Who = :email COLLATE NOCASE
            ORDER BY Id DESC 
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $lastActivity = $stmt->fetchColumn();

        if ($lastActivity) {
            $stmt = $this->pdo->prepare("
                UPDATE Person 
                SET LastSignOut = :lastActivity
                WHERE Email = :email COLLATE NOCASE
            ");
            $stmt->execute([
                ':lastActivity' => $lastActivity,
                ':email'        => $email
            ]);
        }

        $stmt = $this->pdo->prepare("
            UPDATE Person 
            SET LastSignIn = :now
            WHERE Email = :email COLLATE NOCASE
        ");
        $stmt->execute([
            ':now'   => date('Y-m-d H:i:s'),
            ':email' => $email
        ]);
    }
}
