<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use stdClass;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\GravatarHandler;
use app\helpers\PersonPreferences;
use app\helpers\WebApp;
use app\modules\Common\interfaces\NewsProviderInterface;
use app\modules\Common\services\EmailService;
use app\valueObjects\EmailMessage;
use app\modules\User\valueObjects\EventRegistrationRow;
use app\valueObjects\IdRow;
use app\valueObjects\MemberGroupRow;
use app\valueObjects\Person;
use app\modules\Article\valueObjects\PersonNameRow;

/**
 * @phpstan-import-type PersonRow from Person
 * @phpstan-import-type IdRowShape from IdRow
 * @phpstan-import-type PersonNameRowShape from PersonNameRow
 */
class MemberDataHelper extends Data implements NewsProviderInterface
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
        $query = $this->pdo->prepare("
            SELECT Individual.Id FROM Individual
            INNER JOIN Member ON Member.Id = Individual.Id
            WHERE Individual.Email = '' AND Individual.Type = 'Member'
        ");
        $query->execute();
        $row = $query->fetch(PDO::FETCH_OBJ);
        $id = ($row instanceof stdClass && isset($row->Id)) ? $row->Id : null;

        if ($id === null) {
            $this->pdo->prepare("
                INSERT INTO Individual (Type, Email, FirstName, LastName)
                VALUES ('Member', '', '', '')
            ")->execute();
            $id = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare("
                INSERT INTO Member (Id, Imported) VALUES (:id, 0)
            ")->execute([':id' => $id]);
        }

        return (int) $id;
    }

    /**
     * @return array<int, string>
     */
    public function getEmailsOfInterestedPeople(?int $idGroup, ?int $idEventType, ?int $dayOfWeek, string $timeOfDay): array
    {
        $members = $this->getInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
        $filteredEmails = [];
        foreach ($members as $member) {
            $filteredEmails[] = $member->Email;
        }
        return $filteredEmails;
    }

    /**
     * @return list<MemberGroupRow>
     */
    private function getInterestedPeople(?int $idGroup, ?int $idEventType, ?int $dayOfWeek, string $timeOfDay): array
    {
        $members = $this->getMembersInGroup($idGroup);
        $filteredPeople = [];
        foreach ($members as $member) {
            if (
                $this->personPreferences->isPersonInterested(
                    $member->Preferences,
                    $member->Availabilities,
                    $idEventType,
                    $dayOfWeek,
                    $timeOfDay
                )
            ) {
                $filteredPeople[] = $member;
            }
        }
        return $filteredPeople;
    }

    /**
     * @return array<int, \stdClass>
     */
    public function getMembersAlerts(): array
    {
        $query = "
            SELECT 
                i.FirstName || ' ' || i.LastName || 
                CASE 
                    WHEN i.NickName IS NOT NULL AND i.NickName != '' THEN ' (' || i.NickName || ')'
                    ELSE ''
                END AS clubMember,
                CASE 
                    WHEN m.Preferences LIKE '%noAlerts%' THEN 'X'
                    ELSE ''
                END AS NoAlert,
                CASE 
                    WHEN m.Preferences LIKE '%newEvent%' THEN 'X'
                    ELSE ''
                END AS NewEvent,
                CASE 
                    WHEN m.Preferences LIKE '%newArticle%' THEN 'X'
                    ELSE ''
                END AS NewArticle
            FROM Individual AS i
            INNER JOIN Member AS m ON m.Id = i.Id
            WHERE (m.Preferences LIKE '%noAlerts%' 
                OR m.Preferences LIKE '%newEvent%' 
                OR m.Preferences LIKE '%newArticle%')
            AND i.Inactivated = 0
            ORDER BY clubMember
        ";
        $stmt = $this->pdo->query($query);
        if ($stmt === false) {
            return [];
        }
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) {
            return $news;
        }

        $sql = "
            SELECT i.Id, i.Email, i.FirstName, i.LastName, m.PresentationLastUpdate
            FROM Individual AS i
            INNER JOIN Member AS m ON m.Id = i.Id
            WHERE m.InPresentationDirectory = 1
            AND m.PresentationLastUpdate >= :searchFrom
            AND i.Email != :email
            ORDER BY m.PresentationLastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':searchFrom' => $searchFrom,
            ':email'      => $connectedUser->person->Email,
        ]);
        $presentations = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($presentations as $presentation) {
            $fullName = trim($presentation->FirstName . ' ' . $presentation->LastName);
            if (empty($fullName)) {
                $fullName = $presentation->Email;
            }
            $news[] = [
                'type'  => 'presentation',
                'id'    => $presentation->Id,
                'title' => 'Présentation de ' . $fullName,
                'date'  => $presentation->PresentationLastUpdate,
                'url'   => '/user/presentation/' . $presentation->Id,
            ];
        }
        return $news;
    }

    /**
     * @return list<MemberGroupRow>
     */
    public function getMembersInGroup(?int $idGroup): array
    {
        $innerJoin = $and = '';
        if ($idGroup !== null) {
            $innerJoin = 'INNER JOIN MemberGroup ON MemberGroup.IdMember = Individual.Id';
            $and       = 'AND MemberGroup.IdGroup = ' . $idGroup;
        }
        $stmt = $this->pdo->query("
            SELECT Individual.Id AS Id, Individual.Id AS PersonId, Individual.FirstName, Individual.LastName,
                Individual.Email, Member.Preferences, Member.Availabilities,
                0 AS InPresentationDirectory, 0 AS ShowPhoneInPresentationDirectory,
                0 AS ShowEmailInPresentationDirectory
            FROM Individual
            INNER JOIN Member ON Member.Id = Individual.Id
            $innerJoin
            WHERE Individual.Inactivated = 0 $and
            ORDER BY Individual.FirstName, Individual.LastName
        ");
        if ($stmt === false) {
            return [];
        }
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        return array_values(array_map(
            static fn(stdClass $row): MemberGroupRow => MemberGroupRow::fromStdClass($row),
            $rows
        ));
    }

    /**
     * @return array<int, \stdClass>
     */
    public function getMembersInGroupForDirectory(int $groupId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT i.*, m.Presentation, m.PresentationLastUpdate,
                            m.InPresentationDirectory, m.UseGravatar
            FROM Individual AS i
            INNER JOIN Member AS m ON m.Id = i.Id
            INNER JOIN MemberGroup AS mg ON mg.IdMember = i.Id
            WHERE mg.IdGroup = ?
            AND m.InPresentationDirectory = 1
            AND i.Inactivated = 0
            ORDER BY i.FirstName, i.LastName
        ");
        $stmt->execute([$groupId]);
        $members = $stmt->fetchAll(PDO::FETCH_OBJ);

        $gravatarHandler = new GravatarHandler();
        foreach ($members as $member) {
            $member->UserImg = WebApp::getUserImg($member, $gravatarHandler);
        }
        return $members;
    }

    /**
     * @return array<int, string>
     */
    public function getMemberWantedToBeAlerted(int $idArticle): array
    {
        $idGroup = null;
        if ($group = $this->get('Article', ['Id' => $idArticle], 'IdGroup AS Id')) {
            /** @var IdRowShape $group */
            $idGroup = IdRow::fromStdClass($group)->Id;
        }
        $idSurvey = null;
        if ($survey = $this->get('Survey', ['IdArticle' => $idArticle], 'Id')) {
            /** @var IdRowShape $survey */
            $idSurvey = IdRow::fromStdClass($survey)->Id;
        }
        $idOrder = null;
        if ($order = $this->get('Order', ['IdArticle' => $idArticle], 'Id')) {
            /** @var IdRowShape $order */
            $idOrder = IdRow::fromStdClass($order)->Id;
        }

        $members = $this->getMembersInGroup($idGroup);
        $filteredEmails = [];

        foreach ($members as $member) {
            if (empty($member->Preferences)) {
                continue;
            }

            $preferencesData = json_decode($member->Preferences, true);
            if (!is_array($preferencesData)) {
                continue;
            }

            $eventTypesRaw = $preferencesData['eventTypes'] ?? [];
            if (!is_array($eventTypesRaw)) {
                continue;
            }
            /** @var array<string, mixed> $eventTypes */
            $eventTypes = $eventTypesRaw;

            $articlePrefsRaw = $eventTypes['newArticle'] ?? [];
            if (!is_array($articlePrefsRaw) || empty($articlePrefsRaw['enabled'])) {
                continue;
            }
            /** @var array<string, mixed> $articlePrefs */
            $articlePrefs = $articlePrefsRaw;

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
                $filteredEmails[] = $member->Email;
                $this->set('Message', [
                    'EventId'  => null,
                    'PersonId' => $member->PersonId,
                    'Text'     => "Nouvel article publié\n\n/article/{$idArticle} (->{$member->Email})",
                    'From'     => 'Webapp',
                ]);
            }
        }
        return $filteredEmails;
    }

    public function getPublisher(?int $idMember): string|null
    {
        if ($idMember === null) {
            return null;
        }
        $person = $this->get('Individual', ['Id' => $idMember], 'FirstName, LastName');
        if ($person === false) {
            return "publié par ?";
        }
        /** @var PersonNameRowShape $person */
        $personName = PersonNameRow::fromStdClass($person);
        return "publié par " . $personName->FirstName . ' ' . $personName->LastName;
    }

    public function getWebmasterEmail(): string
    {
        $query = $this->pdo->query('
            SELECT i.Email FROM Individual AS i
            INNER JOIN MemberGroup  ON MemberGroup.IdMember = i.Id
            INNER JOIN "Group"      ON "Group".Id = MemberGroup.IdGroup
            INNER JOIN GroupAuthorization ON "Group".Id = GroupAuthorization.IdGroup
            INNER JOIN Authorization      ON GroupAuthorization.IdAuthorization = Authorization.Id
            WHERE Authorization.Name = "Webmaster"
        ');
        if ($query === false) {
            return '';
        }
        $email = $query->fetchColumn();
        return is_string($email) ? $email : '';
    }

    public function sendRegistrationLink(string $adminEmail, string $name, string $emailContact, EventRegistrationRow $event): bool
    {
        // Cherche un Contact existant via Individual
        $individual = $this->pdo->prepare("
            SELECT i.Id, c.Token FROM Individual AS i
            INNER JOIN Contact AS c ON c.Id = i.Id
            WHERE i.Email = :email AND i.Type = 'Contact'
        ");
        $individual->execute([':email' => $emailContact]);
        $contact = $individual->fetch(PDO::FETCH_OBJ);

        $token = bin2hex(random_bytes(32));

        if (!($contact instanceof stdClass)) {
            // Création dans Individual puis Contact
            $stmtInd = $this->pdo->prepare("
                INSERT INTO Individual (Type, Email, FirstName, LastName, NickName)
                VALUES ('Contact', :email, :firstName, '', :nickName)
            ");
            $stmtInd->execute([
                ':email'     => $emailContact,
                ':firstName' => $name,
                ':nickName'  => $name,
            ]);
            $newId = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare("
                INSERT INTO Contact (Id, Token, TokenCreatedAt)
                VALUES (:id, :token, :tokenCreatedAt)
            ")->execute([
                ':id'             => $newId,
                ':token'          => $token,
                ':tokenCreatedAt' => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Mise à jour du token
            $this->pdo->prepare("
                UPDATE Contact SET Token = :token, TokenCreatedAt = :tokenCreatedAt
                WHERE Id = :id
            ")->execute([
                ':token'          => $token,
                ':tokenCreatedAt' => date('Y-m-d H:i:s'),
                ':id'             => $contact->Id,
            ]);
        }

        $registrationLink = Webapp::getBaseUrl() . "event/{$event->Id}/{$token}";
        $subject = "Lien d'inscription pour " . $event->Summary;

        $emailMessage = new EmailMessage(
            from: $adminEmail,
            to: $emailContact,
            subject: $subject,
            body: $registrationLink,
            isHtml: false
        );
        return $this->emailService->send($emailMessage);
    }

    public function updateActivity(string $email): void
    {
        $stmt = $this->pdoForLog->prepare("
            SELECT CreatedAt FROM Log
            WHERE Who = :email COLLATE NOCASE
            ORDER BY Id DESC LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $lastActivity = $stmt->fetchColumn();

        if ($lastActivity) {
            $this->pdo->prepare("
                UPDATE Member SET LastSignOut = :lastActivity
                WHERE Id = (SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE)
            ")->execute([
                ':lastActivity' => $lastActivity,
                ':email'        => $email,
            ]);
        }

        $this->pdo->prepare("
            UPDATE Member SET LastSignIn = :now
            WHERE Id = (SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE)
        ")->execute([
            ':now'   => date('Y-m-d H:i:s'),
            ':email' => $email,
        ]);
    }
}
