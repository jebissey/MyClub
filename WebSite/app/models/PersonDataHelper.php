<?php

declare(strict_types=1);

namespace app\models;

use InvalidArgumentException;
use PDO;
use RuntimeException;
use stdClass;
use Throwable;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\GravatarHandler;
use app\helpers\PersonPreferences;
use app\helpers\WebApp;
use app\modules\Common\interfaces\NewsProviderInterface;
use app\modules\Common\services\EmailService;
use app\modules\Common\valueObjects\EmailMessage;
use app\modules\User\valueObjects\EventRegistrationRow;

/**
 * @phpstan-type PersonGroupRow object{
 *     PersonId: int,
 *     Id: int,
 *     FirstName: string|null,
 *     LastName: string|null,
 *     Email: string|null,
 *     Preferences: string|null,
 *     Availabilities: string|null,
 *     InPresentationDirectory: int,
 *     ShowPhoneInPresentationDirectory: int|string,
 *     ShowEmailInPresentationDirectory: int|string
 * }
 * @phpstan-type NewArticlePrefs array{
 *     enabled?: bool,
 *     pollOnly?: bool,
 *     orderOnly?: bool,
 *     poll_or_order?: bool
 * }
 * @phpstan-type PersonPreferencesShape array{
 *     eventTypes?: array{
 *         newArticle?: NewArticlePrefs
 *     }
 * }
 * @phpstan-type CsvColumnMapping array{
 *     email: int|string,
 *     firstName: int|string,
 *     lastName: int|string,
 *     phone: int|string
 * }
 * @phpstan-type CsvImportResults array{
 *     created: int,
 *     updated: int,
 *     deactivated: int,
 *     errors: int,
 *     processedEmails: list<string>,
 *     messages: list<string>
 * }
 */
class PersonDataHelper extends Data implements NewsProviderInterface
{
    public function __construct(
        Application $application,
        private PersonPreferences $personPreferences,
        private EmailService $emailService
    ) {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    public function create(): int
    {
        // On crée d'abord un Individual "vide", puis le Member associé
        $query = $this->pdo->prepare("SELECT Id FROM Individual WHERE Email = '' AND Type = 'Member'");
        $query->execute();
        /** @var object{Id: int}|false $row */
        $row = $query->fetch(PDO::FETCH_OBJ);
        $id = $row !== false ? $row->Id : null;

        if ($id === null) {
            $stmt = $this->pdo->prepare("
                INSERT INTO Individual (Type, Email, FirstName, LastName)
                VALUES ('Member', '', '', '')
            ");
            $stmt->execute([]);
            $id = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("
                INSERT INTO Member (Id, Imported)
                VALUES (:id, 0)
            ");
            $stmt->execute([':id' => $id]);
        }

        return $id;
    }

    /**
     * @return array<string, int>
     */
    public function getAllPersons(): array
    {
        $query = $this->pdo->query(
            "SELECT i.Id, LOWER(i.Email) AS EmailKey
             FROM Individual i
             INNER JOIN Member m ON m.Id = i.Id"
        );

        if ($query === false) {
            Application::unreachable("Query failed", __FILE__, __LINE__);
        }

        /** @var list<object{Id:int, EmailKey:string}> $rows */
        $rows = $query->fetchAll(PDO::FETCH_OBJ);

        return array_column($rows, 'Id', 'EmailKey');
    }

    /**
     * Coordonnées (Email, Phone, FirstName, LastName, NickName) de chaque membre
     * actif, indexées par Email — pour la copie presse-papier des contacts d'un
     * groupe/type d'événement filtré (EventEmailController::copyEmails()).
     *
     * @return array<string, object{Email: string, Phone: string|null, FirstName: string, LastName: string|null, NickName: string|null}>
     */
    public function getActiveMembersContactInfoByEmail(): array
    {
        $sql = "
            SELECT Individual.Email, Individual.Phone, Individual.FirstName, Individual.LastName, Individual.NickName
            FROM Member
            INNER JOIN Individual ON Individual.Id = Member.Id
            WHERE Member.Inactivated = 0
        ";
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return [];
        }

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $row) {
            $result[$row->Email] = $row;
        }
        return $result;
    }

    /**
     * @return string[]
     */
    public function getEmailsOfInterestedPeople(?int $idGroup, ?int $idEventType, ?int $dayOfWeek, string $timeOfDay): array
    {
        $persons = $this->getInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
        $filteredEmails = [];
        foreach ($persons as $person) {
            if ($person->Email !== null) {
                $filteredEmails[] = $person->Email;
            }
        }
        return $filteredEmails;
    }

    /**
     * @return list<PersonGroupRow>
     */
    public function getInterestedPeople(?int $idGroup, ?int $idEventType, ?int $dayOfWeek, string $timeOfDay): array
    {
        $persons = $this->getPersonsInGroup($idGroup);
        $filteredPeople = [];
        foreach ($persons as $person) {
            if ($person->Email === null) {
                continue;
            }
            if (
                $this->personPreferences->isPersonInterested(
                    $person->Preferences,
                    $person->Availabilities,
                    $idEventType,
                    $dayOfWeek,
                    $timeOfDay
                )
            ) {
                $filteredPeople[] = $person;
            }
        }
        return $filteredPeople;
    }

    /**
     * @return stdClass[]
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
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            WHERE (m.Preferences LIKE '%noAlerts%' 
               OR m.Preferences LIKE '%newEvent%' 
               OR m.Preferences LIKE '%newArticle%')
              AND m.Inactivated = 0
            ORDER BY clubMember
        ";
        $stmt = $this->pdo->query($query);
        if ($stmt === false) {
            Application::unreachable("Query failed", __FILE__, __LINE__);
        }
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @return array<int, array{type: string, id: int, title: string, date: string, url: string}>
     */
    public function getNews(ConnectedUser $connectedUser, string $searchFrom): array
    {
        $news = [];
        if (!($connectedUser->person ?? false)) {
            return $news;
        }

        $sql = "
            SELECT i.Id, i.Email, i.FirstName, i.LastName, m.PresentationLastUpdate
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            WHERE m.InPresentationDirectory = 1
              AND m.PresentationLastUpdate >= :searchFrom
              AND i.Email != :email
            ORDER BY m.PresentationLastUpdate DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':searchFrom' => $searchFrom,
            ':email'      => $connectedUser->person->Email
        ]);

        /** @var array<int, object{Id: int, Email: string, FirstName: string, LastName: string, PresentationLastUpdate: string}> $presentations */
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
                'url'   => '/user/presentation/' . $presentation->Id
            ];
        }
        return $news;
    }

    /**
     * @return stdClass[]
     */
    public function getPersonsForCommunication(
        ?int $groupId,
        ?bool $presentation = null,
        ?bool $password = null,
        ?bool $inPublicMap = null,
        ?bool $desactivated = null
    ): array {
        $joins  = '';
        $wheres = ["i.Email != ''"];
        $params = [];

        if ($groupId !== null) {
            $joins    = 'INNER JOIN MemberGroup mg ON mg.IdMember = m.Id';
            $wheres[] = 'mg.IdGroup = :groupId';
            $params[':groupId'] = $groupId;
        }

        if ($presentation === true) {
            $wheres[] = 'm.InPresentationDirectory = 1';
        } elseif ($presentation === false) {
            $wheres[] = 'm.InPresentationDirectory = 0';
        }

        if ($password === true) {
            $wheres[] = "(m.Password IS NOT NULL AND m.Password != '')";
        } elseif ($password === false) {
            $wheres[] = "(m.Password IS NULL OR m.Password = '')";
        }

        if ($inPublicMap === true) {
            $wheres[] = "(m.MyPublicDataInPresentationDirectory IS NOT NULL AND m.MyPublicDataInPresentationDirectory <> '')";
        } elseif ($inPublicMap === false) {
            $wheres[] = "(m.MyPublicDataInPresentationDirectory IS NULL OR m.MyPublicDataInPresentationDirectory = '')";
        }

        if ($desactivated === true) {
            $wheres[] = "m.Inactivated = 1";
        } elseif ($desactivated === null) {
            $wheres[] = "m.Inactivated = 0";
        }

        $where = implode(' AND ', $wheres);
        $sql = "
            SELECT DISTINCT i.Id, i.FirstName, i.LastName, i.Email
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            $joins
            WHERE $where
            ORDER BY i.FirstName, i.LastName
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @return list<PersonGroupRow>
     */
    public function getPersonsInGroup(?int $idGroup): array
    {
        $innerJoin = $and = '';

        if ($idGroup !== null) {
            $innerJoin = 'INNER JOIN MemberGroup mg ON mg.IdMember = m.Id';
            $and = 'AND mg.IdGroup = ' . (int) $idGroup;
        }

        $query = $this->pdo->query("
            SELECT
                i.Id AS PersonId,
                i.Id AS Id,
                i.FirstName,
                i.LastName,
                i.Email,
                m.Preferences,
                m.Availabilities,
                m.InPresentationDirectory,
                m.ShowPhoneInPresentationDirectory,
                m.ShowEmailInPresentationDirectory
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            $innerJoin
            WHERE m.Inactivated = 0 $and
            ORDER BY i.FirstName, i.LastName
        ");

        if ($query === false) {
            Application::unreachable("Query failed", __FILE__, __LINE__);
        }

        /** @var list<PersonGroupRow> $persons */
        $persons = $query->fetchAll(PDO::FETCH_OBJ);

        return $persons;
    }

    /**
     * @return stdClass[]
     */
    public function getPersonsInGroupForDirectory(int $groupId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT 
                i.Id,
                m.UseGravatar, 
                i.Email,
                i.Avatar,
                i.FirstName,
                i.LastName,
                i.NickName
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            WHERE mg.IdGroup = ?
              AND m.InPresentationDirectory = 1
              AND m.Inactivated = 0
            ORDER BY i.FirstName, i.LastName
        ");
        $stmt->execute([$groupId]);
        $persons = $stmt->fetchAll(PDO::FETCH_OBJ);

        $gravatarHandler = new GravatarHandler();
        foreach ($persons as $person) {
            $person->UserImg = WebApp::computeUserImg(
                $person->UseGravatar === 'yes',
                $person->Email ?? null,
                $person->Avatar ?? null,
                $gravatarHandler
            );
        }
        return $persons;
    }

    /**
     * @return string[]
     */
    public function getPersonWantedToBeAlerted(int $idArticle): array
    {
        $idGroup = null;
        $group = $this->get('Article', ['Id' => $idArticle], 'IdGroup');
        if ($group !== false) {
            /** @var object{IdGroup: int} $group */
            $idGroup = $group->IdGroup;
        }

        $idSurvey = null;
        $survey = $this->get('Survey', ['IdArticle' => $idArticle], 'Id');
        if ($survey !== false) {
            /** @var object{Id: int} $survey */
            $idSurvey = $survey->Id;
        }

        $idOrder = null;
        $order = $this->get('Order', ['IdArticle' => $idArticle], 'Id');
        if ($order !== false) {
            /** @var object{Id: int} $order */
            $idOrder = $order->Id;
        }

        $persons = $this->getPersonsInGroup($idGroup);
        $filteredEmails = [];
        foreach ($persons as $person) {
            if (empty($person->Preferences)) {
                continue;
            }
            /** @var PersonPreferencesShape|null $preferences */
            $preferences = json_decode($person->Preferences, true);
            if (!is_array($preferences) || empty($preferences['eventTypes']['newArticle']['enabled'])) {
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

            if ($include && $person->Email !== null) {
                $filteredEmails[] = $person->Email;
                $this->set('Message', [
                    'EventId'  => null,
                    'PersonId' => $person->PersonId,
                    'Text'     => "Nouvel article publié\n\n/article/{$idArticle} (->{$person->Email})",
                    'From'     => 'Webapp'
                ]);
            }
        }
        return $filteredEmails;
    }

    public function getPublisher(?int $idPerson): string|null
    {
        if ($idPerson === null) {
            return null;
        }
        $person = $this->get('Individual', ['Id' => $idPerson], 'FirstName, LastName');
        if ($person === false) {
            return "publié par ?";
        }
        /** @var object{FirstName: string|null, LastName: string|null} $person */
        $name = trim(($person->FirstName ?? '') . ' ' . ($person->LastName ?? ''));
        return "publié par " . $name;
    }

    /**
     * @return stdClass[]
     */
    public function getRedactors(): array
    {
        $query = $this->pdo->query("
            SELECT i.Id AS PersonId, i.FirstName, i.LastName, i.NickName, i.Email
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            INNER JOIN GroupAuthorization ga ON ga.IdGroup = mg.IdGroup
            WHERE m.Inactivated = 0
              AND ga.IdAuthorization = 4
            GROUP BY i.Id
            ORDER BY i.FirstName, i.LastName
        ");
        if ($query === false) {
            Application::unreachable("Query failed", __FILE__, __LINE__);
        }
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function getWebmasterEmail(): string
    {
        $query = $this->pdo->query('
            SELECT i.Email
            FROM Member m
            INNER JOIN Individual i ON i.Id = m.Id
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            INNER JOIN "Group" g ON g.Id = mg.IdGroup
            INNER JOIN GroupAuthorization ga ON ga.IdGroup = g.Id
            INNER JOIN Authorization a ON a.Id = ga.IdAuthorization
            WHERE a.Name = "Webmaster"
        ');
        if ($query === false) {
            Application::unreachable("Query failed", __FILE__, __LINE__);
        }
        $email = $query->fetchColumn();
        if (!is_string($email)) {
            Application::unreachable("Webmaster email not found", __FILE__, __LINE__);
        }
        return $email;
    }

    /**
     * Imports persons from a CSV file.
     * ...
     * @param CsvColumnMapping $mapping
     * @param array<string, int> $existingPersons
     * @return CsvImportResults
     */
    public function importFromCsvFile(
        string $filePath,
        int $headerRow,
        array $mapping,
        array $existingPersons
    ): array {
        foreach (['email', 'firstName', 'lastName', 'phone'] as $key) {
            if (!array_key_exists($key, $mapping)) {
                throw new InvalidArgumentException("Clé de mapping manquante : {$key}");
            }
        }
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException("Fichier CSV introuvable ou illisible : $filePath");
        }
        $file = fopen($filePath, 'r');
        if ($file === false) {
            throw new RuntimeException("Impossible d'ouvrir le fichier CSV : $filePath");
        }

        $results = [
            'created'         => 0,
            'updated'         => 0,
            'deactivated'     => 0,
            'errors'          => 0,
            'processedEmails' => [],
            'messages'        => [],
        ];

        $this->pdo->beginTransaction();

        try {
            // Upsert Individual + Member
            $stmtUpsertIndividual = $this->pdo->prepare("
                INSERT INTO Individual (Type, Email, FirstName, LastName, Phone)
                VALUES ('Member', :email, :firstName, :lastName, :phone)
                ON CONFLICT(Email) DO UPDATE SET
                    FirstName = excluded.FirstName,
                    LastName  = excluded.LastName,
                    Phone     = excluded.Phone
            ");

            $stmtUpsertMember = $this->pdo->prepare("
                INSERT INTO Member (Id, Imported, Inactivated)
                VALUES (:id, 1, 0)
                ON CONFLICT(Id) DO UPDATE SET
                    Imported    = 1,
                    Inactivated = 0
            ");

            $processedEmailKeys = [];
            $currentRow = 0;
            while (($data = fgetcsv($file, 0, ',', '"', '')) !== false) {
                $currentRow++;
                if ($currentRow <= $headerRow) {
                    continue;
                }
                $email = filter_var($data[$mapping['email']] ?? '', FILTER_VALIDATE_EMAIL);
                if ($email === false) {
                    $results['errors']++;
                    $results['messages'][] = "Ligne $currentRow : adresse email invalide {$data[$mapping['email']]}.";
                    continue;
                }
                $phone = preg_replace('/[^\d\s+\-()\.]/', '', $data[$mapping['phone']] ?? '');
                $personData = [
                    'email'     => $email,
                    'firstName' => mb_substr(trim($data[$mapping['firstName']] ?? ''), 0, 100),
                    'lastName'  => mb_substr(trim($data[$mapping['lastName']] ?? ''), 0, 100),
                    'phone'     => mb_substr(is_string($phone) ? $phone : '', 0, 20),
                ];

                $emailKey   = strtolower($personData['email']);
                $existingId = $existingPersons[$emailKey] ?? null;

                $stmtUpsertIndividual->execute([
                    ':email'     => $personData['email'],
                    ':firstName' => $personData['firstName'],
                    ':lastName'  => $personData['lastName'],
                    ':phone'     => $personData['phone'],
                ]);

                // Récupérer l'Id (nouveau ou existant)
                $id = $existingId;
                if ($id === null) {
                    $id = (int) $this->pdo->lastInsertId();
                    // Si ON CONFLICT a fait un UPDATE, lastInsertId peut être 0 → on relit
                    if ($id === 0) {
                        $stmt = $this->pdo->prepare("SELECT Id FROM Individual WHERE Email = :email");
                        $stmt->execute([':email' => $personData['email']]);
                        $id = (int) $stmt->fetchColumn();
                    }
                }

                $stmtUpsertMember->execute([':id' => $id]);

                if ($existingId !== null) {
                    $results['updated']++;
                } else {
                    $results['created']++;
                    $results['messages'][] = '(+) ' . $personData['email'];
                    $existingPersons[$emailKey] = $id;
                }

                $processedEmailKeys[$emailKey] = true;
                $results['processedEmails'][]  = $personData['email'];
            }

            // Désactivation des membres non présents dans le CSV
            $idsToDeactivate = [];
            foreach ($existingPersons as $emailKey => $id) {
                if (!isset($processedEmailKeys[$emailKey]) && $id !== 1) {
                    $idsToDeactivate[] = $id;
                }
            }
            if (!empty($idsToDeactivate)) {
                $placeholders = implode(',', array_fill(0, count($idsToDeactivate), '?'));
                $stmtDeactivate = $this->pdo->prepare("
                    UPDATE Member
                    SET Inactivated = 1
                    WHERE Id IN ($placeholders)
                ");
                $stmtDeactivate->execute($idsToDeactivate);
                $results['deactivated'] = $stmtDeactivate->rowCount();
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        } finally {
            fclose($file);
        }
        return $results;
    }

    public function sendRegistrationLink(
        string $adminEmail,
        string $name,
        string $emailContact,
        EventRegistrationRow $event
    ): bool {
        // Contact est maintenant un sous-type d'Individual
        /** @var object{Id: int}|false $individual */
        $individual = $this->get('Individual', ['Email' => $emailContact], '*');

        if ($individual === false) {
            // Créer Individual + Contact
            $this->set('Individual', [
                'Type'      => 'Contact',
                'Email'     => $emailContact,
                'FirstName' => $name,
                'LastName'  => '',
                'NickName'  => $name,
            ]);
            $individualId = (int) $this->pdo->lastInsertId();

            $token = bin2hex(random_bytes(32));
            $this->set('Contact', [
                'Id'             => $individualId,
                'Token'          => $token,
                'TokenCreatedAt' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $individualId = $individual->Id;
            $token = bin2hex(random_bytes(32));
            // Mettre à jour le token du Contact
            $this->set(
                'Contact',
                [
                    'Token'          => $token,
                    'TokenCreatedAt' => date('Y-m-d H:i:s'),
                ],
                ['Id' => $individualId]
            );
        }

        $registrationLink = WebApp::getBaseUrl() . "event/{$event->Id}/{$token}";
        $subject = "Lien d'inscription pour " . $event->Summary;
        $body = $registrationLink;

        $emailMessage = new EmailMessage(
            from: $adminEmail,
            to: $emailContact,
            subject: $subject,
            body: $body,
            isHtml: false
        );
        return $this->emailService->send($emailMessage);
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
                UPDATE Member
                SET LastSignOut = :lastActivity
                WHERE Id = (
                    SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE
                )
            ");
            $stmt->execute([
                ':lastActivity' => $lastActivity,
                ':email'        => $email
            ]);
        }

        $stmt = $this->pdo->prepare("
            UPDATE Member
            SET LastSignIn = :now
            WHERE Id = (
                SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE
            )
        ");
        $stmt->execute([
            ':now'   => date('Y-m-d H:i:s'),
            ':email' => $email
        ]);
    }
}
