<?php

declare(strict_types=1);

namespace app\models;

use InvalidArgumentException;
use PDO;
use PDOStatement;
use RuntimeException;
use stdClass;
use Throwable;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\GravatarHandler;
use app\helpers\MemberCustomFields;
use app\helpers\OdsReader;
use app\helpers\OdsWriter;
use app\helpers\PersonPreferences;
use app\helpers\To;
use app\helpers\WebApp;
use app\modules\Common\interfaces\NewsProviderInterface;
use app\modules\Common\services\EmailService;
use app\modules\Common\valueObjects\EmailMessage;
use app\modules\PersonManager\valueObjects\CustomFieldDefinition;
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
 *     phone: int|string,
 *     custom?: array<string, int>
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
final class PersonDataHelper extends Data implements NewsProviderInterface
{
    /**
     * Fragment de jointure commun Member/Individual (alias m/i), utilisé par
     * la plupart des requêtes de cette classe.
     */
    private const MEMBER_INDIVIDUAL_JOIN = 'FROM Member m INNER JOIN Individual i ON i.Id = m.Id';

    public function __construct(
        Application $application,
        private readonly PersonPreferences $personPreferences,
        private readonly EmailService $emailService
    ) {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * Exécute une requête SELECT et échoue bruyamment (Application::unreachable)
     * si la préparation/exécution échoue, plutôt que de laisser un `false` se
     * propager silencieusement.
     */
    private function queryOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            Application::unreachable("Query failed", __FILE__, __LINE__);
        }
        return $stmt;
    }

    /**
     * Ajoute une clause WHERE conditionnelle selon un filtre tri-état
     * (true / false / null=ignoré).
     *
     * @param list<string> $wheres
     */
    private function addTriStateWhere(array &$wheres, ?bool $value, string $trueSql, string $falseSql): void
    {
        if ($value === true) {
            $wheres[] = $trueSql;
        } elseif ($value === false) {
            $wheres[] = $falseSql;
        }
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
        $stmt = $this->queryOrFail(
            "SELECT i.Id, LOWER(i.Email) AS EmailKey " . self::MEMBER_INDIVIDUAL_JOIN
        );

        /** @var list<object{Id:int, EmailKey:string}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

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
            SELECT i.Email, i.Phone, i.FirstName, i.LastName, i.NickName
            " . self::MEMBER_INDIVIDUAL_JOIN . "
            WHERE m.Inactivated = 0
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
        $sql = "
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
            " . self::MEMBER_INDIVIDUAL_JOIN . "
            WHERE (m.Preferences LIKE '%noAlerts%' 
               OR m.Preferences LIKE '%newEvent%' 
               OR m.Preferences LIKE '%newArticle%')
              AND m.Inactivated = 0
            ORDER BY clubMember
        ";
        return $this->queryOrFail($sql)->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @return list<object{
     *     Id: int|string,
     *     Inactivated: int|bool|string|null,
     *     Email: string,
     *     FirstName: string|null,
     *     LastName: string|null,
     *     Phone: string|null,
     *     CustomFields: string|null
     * }>
     */
    public function getMembersForExport(): array
    {
        $stmt = $this->pdo->query("
            SELECT i.Id, i.Email, i.FirstName, i.LastName, i.Phone, m.CustomFields, m.Inactivated
            FROM Individual i
            JOIN Member m ON m.Id = i.Id
            ORDER BY i.LastName, i.FirstName
        ");
        if ($stmt === false) {
            throw new RuntimeException("Impossible de lire les membres pour l'export");
        }
        /** @var list<object{Id: int|string, Inactivated: int|bool|string|null, Email: string, FirstName: string|null, LastName: string|null, Phone: string|null, CustomFields: string|null}> $members */
        $members = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $members;
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
            " . self::MEMBER_INDIVIDUAL_JOIN . "
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

        $this->addTriStateWhere(
            $wheres,
            $presentation,
            'm.InPresentationDirectory = 1',
            'm.InPresentationDirectory = 0'
        );

        $this->addTriStateWhere(
            $wheres,
            $password,
            "(m.Password IS NOT NULL AND m.Password != '')",
            "(m.Password IS NULL OR m.Password = '')"
        );

        $this->addTriStateWhere(
            $wheres,
            $inPublicMap,
            "(m.MyPublicDataInPresentationDirectory IS NOT NULL AND m.MyPublicDataInPresentationDirectory <> '')",
            "(m.MyPublicDataInPresentationDirectory IS NULL OR m.MyPublicDataInPresentationDirectory = '')"
        );

        if ($desactivated === true) {
            $wheres[] = "m.Inactivated = 1";
        } elseif ($desactivated === null) {
            $wheres[] = "m.Inactivated = 0";
        }

        $where = implode(' AND ', $wheres);
        $sql = "
            SELECT DISTINCT i.Id, i.FirstName, i.LastName, i.Email
            " . self::MEMBER_INDIVIDUAL_JOIN . "
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

        $sql = "
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
            " . self::MEMBER_INDIVIDUAL_JOIN . "
            $innerJoin
            WHERE m.Inactivated = 0 $and
            ORDER BY i.FirstName, i.LastName
        ";

        /** @var list<PersonGroupRow> $persons */
        $persons = $this->queryOrFail($sql)->fetchAll(PDO::FETCH_OBJ);

        return $persons;
    }

    /**
     * @return stdClass[]
     */
    public function getPersonsInGroupForDirectory(int $groupId): array
    {
        $sql = "
            SELECT DISTINCT 
                i.Id,
                m.UseGravatar, 
                i.Email,
                i.Avatar,
                i.FirstName,
                i.LastName,
                i.NickName
            " . self::MEMBER_INDIVIDUAL_JOIN . "
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            WHERE mg.IdGroup = ?
              AND m.InPresentationDirectory = 1
              AND m.Inactivated = 0
            ORDER BY i.FirstName, i.LastName
        ";
        $stmt = $this->pdo->prepare($sql);
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
        $sql = "
            SELECT i.Id AS PersonId, i.FirstName, i.LastName, i.NickName, i.Email
            " . self::MEMBER_INDIVIDUAL_JOIN . "
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            INNER JOIN GroupAuthorization ga ON ga.IdGroup = mg.IdGroup
            WHERE m.Inactivated = 0
              AND ga.IdAuthorization = 4
            GROUP BY i.Id
            ORDER BY i.FirstName, i.LastName
        ";
        return $this->queryOrFail($sql)->fetchAll(PDO::FETCH_OBJ);
    }

    public function getWebmasterEmail(): string
    {
        $sql = '
            SELECT i.Email
            ' . self::MEMBER_INDIVIDUAL_JOIN . '
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            INNER JOIN "Group" g ON g.Id = mg.IdGroup
            INNER JOIN GroupAuthorization ga ON ga.IdGroup = g.Id
            INNER JOIN Authorization a ON a.Id = ga.IdAuthorization
            WHERE a.Name = "Webmaster"
        ';
        $email = $this->queryOrFail($sql)->fetchColumn();
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
     * @param callable(string): string $t Translator used for user-facing messages
     * @return CsvImportResults
     */
    public function importFromCsvFile(
        string $filePath,
        int $headerRow,
        array $mapping,
        array $existingPersons,
        callable $t
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

        $definitions = $this->loadCustomFieldDefinitions();
        $definitionsByKey = [];
        foreach ($definitions as $definition) {
            $definitionsByKey[$definition->key] = $definition;
        }
        $customMapping = $mapping['custom'] ?? [];

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

            $stmtReadCustom = $this->pdo->prepare('SELECT CustomFields FROM Member WHERE Id = :id');
            $stmtWriteCustom = $this->pdo->prepare('UPDATE Member SET CustomFields = :customFields WHERE Id = :id');

            $processedEmailKeys = [];
            $currentRow = 0;
            while (($data = fgetcsv($file, 0, ',', '"', '')) !== false) {
                $currentRow++;
                if ($currentRow <= $headerRow) {
                    continue;
                }
                $rawEmail  = trim((string)($data[$mapping['email']] ?? ''));
                $firstName = mb_substr(trim((string)($data[$mapping['firstName']] ?? '')), 0, 100);
                $lastName  = mb_substr(trim((string)($data[$mapping['lastName']] ?? '')), 0, 100);
                $phoneRaw  = preg_replace('/[^\d\s+\-()\.]/', '', (string)($data[$mapping['phone']] ?? ''));
                $phone     = mb_substr(is_string($phoneRaw) ? $phoneRaw : '', 0, 20);

                $email = filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
                if ($email === false) {
                    // Ligne totalement vide : on l'ignore (sinon on créerait un membre fantôme)
                    if ($rawEmail === '' && $firstName === '' && $lastName === '' && $phone === '') {
                        continue;
                    }
                    $email = self::fictiveEmail($firstName, $lastName, $phone, $rawEmail);
                    $results['messages'][] = str_replace(
                        ['{line}', '{email}'],
                        [(string)$currentRow, $email],
                        $t('import.warning.fictive_email')
                    );
                }

                $personData = [
                    'email'     => $email,
                    'firstName' => $firstName,
                    'lastName'  => $lastName,
                    'phone'     => $phone,
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

                if ($definitions !== [] && $customMapping !== []) {
                    $rawCustom = MemberCustomFields::extractFromRow($definitions, $customMapping, $data);
                    /** @var array<string, string|int|float> $importedCustom */
                    $importedCustom = [];
                    foreach ($rawCustom as $key => $raw) {
                        $definition = $definitionsByKey[$key];
                        $normalized = MemberCustomFields::normalizeValue($definition->type, $raw);
                        if ($normalized === null) {
                            $results['messages'][] = str_replace(
                                ['{line}', '{value}', '{field}'],
                                [(string)$currentRow, $raw, $definition->label],
                                $t('import.error.invalid_custom_value')
                            );
                            continue;
                        }
                        $importedCustom[$key] = $normalized;
                    }
                    if ($importedCustom !== []) {
                        $stmtReadCustom->execute([':id' => $id]);
                        $json = $stmtReadCustom->fetchColumn();
                        $current = MemberCustomFields::decodeValues(is_string($json) ? $json : null);
                        // L'import l'emporte pour les clés fournies ; les autres valeurs sont conservées.
                        $stmtWriteCustom->execute([
                            ':id' => $id,
                            ':customFields' => MemberCustomFields::encodeValues($importedCustom + $current),
                        ]);
                    }
                }

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

    /**
     * Synchronise les membres avec un fichier .ods produit par l'export.
     * - ligne sans Id : nouveau membre ; Id déjà présent plusieurs fois : la ligne dont l'email correspond
     *   à la base (sinon la première) met à jour le membre, les autres sont ajoutées ;
     * - Id absent du fichier, ou colonne Actif différente de ☑ : membre désactivé ;
     * - $dryRun : tout est exécuté puis annulé (aperçu des compteurs).
     *
     * @param callable(string): string $t Traducteur des messages affichés à l'utilisateur
     * @return array{created: int, updated: int, deactivated: int, reactivated: int, errors: int, messages: list<string>}
     */
    public function importFromOdsFile(string $filePath, callable $t, bool $dryRun = false): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
            'reactivated' => 0,
            'errors' => 0,
            'messages' => [],
        ];
        $fail = static function (string $message) use (&$results): void {
            $results['errors']++;
            $results['messages'][] = $message;
        };

        $sheet = OdsReader::readFirstSheet($filePath);
        $headerLine = array_key_first($sheet);
        if ($headerLine === null) {
            $fail($t('import.ods.error.empty'));
            return $results;
        }
        $headerCells = $sheet[$headerLine];
        unset($sheet[$headerLine]);

        // Colonnes repérées par leur titre
        $idCol = $this->findOdsColumn($headerCells, ['Id']);
        $activeCol = $this->findOdsColumn($headerCells, $this->languageAliases('export.column.active'));
        $emailCol = $this->findOdsColumn($headerCells, $this->languageAliases('import.form.email'));
        if ($idCol === null || $activeCol === null || $emailCol === null) {
            $missing = [];
            if ($idCol === null) {
                $missing[] = 'Id';
            }
            if ($activeCol === null) {
                $missing[] = $t('export.column.active');
            }
            if ($emailCol === null) {
                $missing[] = $t('import.form.email');
            }
            $fail(str_replace('{column}', implode(', ', $missing), $t('import.ods.error.missing_column')));
            return $results;
        }
        $firstNameCol = $this->findOdsColumn($headerCells, $this->languageAliases('import.form.firstname'));
        $lastNameCol = $this->findOdsColumn($headerCells, $this->languageAliases('import.form.lastname'));
        $phoneCol = $this->findOdsColumn($headerCells, $this->languageAliases('import.form.phone'));

        $definitions = $this->loadCustomFieldDefinitions();
        $customMapping = [];
        foreach ($definitions as $definition) {
            $column = $this->findOdsColumn($headerCells, [$definition->label]);
            if ($column !== null) {
                $customMapping[$definition->key] = $column;
            }
        }

        // État actuel de la base
        /** @var array<int, array{email: string, firstName: string, lastName: string, phone: string, imported: bool, inactivated: bool, customFields: string|null}> $members */
        $members = [];
        /** @var array<string, int> $idByEmail */
        $idByEmail = [];
        $stmt = $this->pdo->query('
            SELECT i.Id, i.Email, i.FirstName, i.LastName, i.Phone, m.Imported, m.Inactivated, m.CustomFields
            FROM Individual i
            JOIN Member m ON m.Id = i.Id
        ');
        if ($stmt === false) {
            throw new RuntimeException("Impossible de lire les membres pour l'import");
        }
        /** @var list<array<string, mixed>> $dbRows */
        $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dbRows as $dbRow) {
            $memberId = To::int($dbRow['Id']);
            $customJson = $dbRow['CustomFields'] ?? null;
            $members[$memberId] = [
                'email' => To::str($dbRow['Email'] ?? ''),
                'firstName' => To::str($dbRow['FirstName'] ?? ''),
                'lastName' => To::str($dbRow['LastName'] ?? ''),
                'phone' => To::str($dbRow['Phone'] ?? ''),
                'imported' => To::bool($dbRow['Imported'] ?? false),
                'inactivated' => To::bool($dbRow['Inactivated'] ?? false),
                'customFields' => is_string($customJson) ? $customJson : null,
            ];
            $idByEmail[strtolower(To::str($dbRow['Email'] ?? ''))] = $memberId;
        }

        // Première passe : lecture des lignes, regroupement par Id
        /** @var list<array{line: int, id: int|null, duplicateOf: int|null, cells: array<int, string>}> $rows */
        $rows = [];
        /** @var array<int, list<int>> $rowsById */
        $rowsById = [];
        /** @var array<int, true> $seenIds */
        $seenIds = [];
        foreach ($sheet as $line => $cells) {
            $rawId = $cells[$idCol] ?? '';
            $id = null;
            if ($rawId !== '') {
                $numeric = str_replace(',', '.', $rawId);
                if (!is_numeric($numeric) || (float)$numeric < 1 || (float)$numeric !== floor((float)$numeric)) {
                    $fail(str_replace(['{line}', '{value}'], [(string)$line, $rawId], $t('import.ods.error.invalid_id')));
                    continue;
                }
                $id = (int)$numeric;
                $seenIds[$id] = true;
                $rowsById[$id][] = count($rows);
            }
            $rows[] = ['line' => $line, 'id' => $id, 'duplicateOf' => null, 'cells' => $cells];
        }
        if ($rows === []) {
            if ($results['errors'] === 0) {
                $fail($t('import.ods.error.empty'));
            }
            return $results; // aucune ligne exploitable : on ne désactive personne
        }

        // Id en double (ligne copiée/collée) : une seule ligne garde l'Id, les autres deviennent des ajouts
        foreach ($rowsById as $id => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            $original = $indexes[0];
            $databaseEmail = isset($members[$id]) ? strtolower($members[$id]['email']) : '';
            if ($databaseEmail !== '') {
                foreach ($indexes as $index) {
                    if (strtolower(trim($rows[$index]['cells'][$emailCol] ?? '')) === $databaseEmail) {
                        $original = $index;
                        break;
                    }
                }
            }
            foreach ($indexes as $index) {
                if ($index !== $original) {
                    $rows[$index]['id'] = null;
                    $rows[$index]['duplicateOf'] = $id;
                }
            }
        }

        $stmtInsertIndividual = $this->pdo->prepare("
            INSERT INTO Individual (Type, Email, FirstName, LastName, Phone)
            VALUES ('Member', :email, :firstName, :lastName, :phone)
        ");
        $stmtInsertMember = $this->pdo->prepare('
            INSERT INTO Member (Id, Imported, Inactivated, CustomFields)
            VALUES (:id, 0, :inactivated, :customFields)
        ');
        $stmtUpdateIndividual = $this->pdo->prepare('
            UPDATE Individual
            SET Email = :email, FirstName = :firstName, LastName = :lastName, Phone = :phone
            WHERE Id = :id
        ');
        $stmtUpdateMember = $this->pdo->prepare('
            UPDATE Member SET Inactivated = :inactivated, CustomFields = :customFields WHERE Id = :id
        ');
        $stmtDeactivate = $this->pdo->prepare('UPDATE Member SET Inactivated = 1 WHERE Id = :id');

        $this->pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                if (!isset($row['line'], $row['cells'])) {
                    continue;
                }
                $line = $row['line'];
                $cells = $row['cells'];
                $id = $row['id'];

                $cellEmail = trim($cells[$emailCol] ?? '');
                $validEmail = $cellEmail === '' ? false : filter_var($cellEmail, FILTER_VALIDATE_EMAIL);
                $emailKey = $validEmail === false ? '' : strtolower($validEmail);
                $firstName = $firstNameCol === null ? null : mb_substr(trim($cells[$firstNameCol] ?? ''), 0, 100);
                $lastName = $lastNameCol === null ? null : mb_substr(trim($cells[$lastNameCol] ?? ''), 0, 100);
                $phone = $phoneCol === null
                    ? null
                    : mb_substr((string)preg_replace('/[^\d\s+\-()\.]/', '', $cells[$phoneCol] ?? ''), 0, 20);
                $activeCell = str_replace("\u{FE0F}", '', trim($cells[$activeCol] ?? ''));

                if ($id !== null) {
                    // --- Membre existant ---
                    if (!isset($members[$id])) {
                        $fail(str_replace(['{line}', '{id}'], [(string)$line, (string)$id], $t('import.ods.error.unknown_id')));
                        continue;
                    }
                    $current = $members[$id];

                    $newEmail = $current['email'];
                    if ($cellEmail !== '') {
                        if ($validEmail === false) {
                            $fail(str_replace(['{line}', '{email}'], [(string)$line, $cellEmail], $t('import.ods.error.invalid_email')));
                            continue;
                        }
                        // L'email est la clé de synchronisation des enregistrements importés : jamais modifié
                        if (!$current['imported'] && strtolower($current['email']) !== $emailKey) {
                            if (isset($idByEmail[$emailKey]) && $idByEmail[$emailKey] !== $id) {
                                $fail(str_replace(
                                    ['{line}', '{email}', '{id}'],
                                    [(string)$line, $validEmail, (string)$idByEmail[$emailKey]],
                                    $t('import.ods.error.email_exists')
                                ));
                                continue;
                            }
                            $newEmail = $validEmail;
                        }
                    }
                    $newFirstName = $firstName ?? $current['firstName'];
                    $newLastName = $lastName ?? $current['lastName'];
                    $newPhone = $phone ?? $current['phone'];

                    $currentCustom = MemberCustomFields::decodeValues($current['customFields']);
                    $newCustom = $this->applyOdsCustomFields(
                        $definitions,
                        $customMapping,
                        $cells,
                        $currentCustom,
                        $line,
                        $results['messages'],
                        $t
                    );
                    $newCustomJson = MemberCustomFields::encodeValues($newCustom);
                    $changedCustom = $newCustomJson !== MemberCustomFields::encodeValues($currentCustom);

                    $changedIdentity = $newEmail !== $current['email']
                        || $newFirstName !== $current['firstName']
                        || $newLastName !== $current['lastName']
                        || $newPhone !== $current['phone'];

                    // Actif = ☑ sinon le membre est désactivé (le membre 1 ne l'est jamais)
                    $wantInactive = $id !== 1 && !self::isOdsChecked($activeCell);

                    if ($changedIdentity) {
                        $stmtUpdateIndividual->execute([
                            ':id' => $id,
                            ':email' => $newEmail,
                            ':firstName' => $newFirstName,
                            ':lastName' => $newLastName,
                            ':phone' => $newPhone,
                        ]);
                        if (strtolower($newEmail) !== strtolower($current['email'])) {
                            unset($idByEmail[strtolower($current['email'])]);
                            $idByEmail[strtolower($newEmail)] = $id;
                        }
                    }
                    if ($changedCustom || $wantInactive !== $current['inactivated']) {
                        $stmtUpdateMember->execute([
                            ':id' => $id,
                            ':inactivated' => $wantInactive ? 1 : 0,
                            ':customFields' => $newCustomJson,
                        ]);
                    }
                    if ($changedIdentity || $changedCustom) {
                        $results['updated']++;
                    }
                    if ($wantInactive && !$current['inactivated']) {
                        $results['deactivated']++;
                        $results['messages'][] = '(-) ' . $newEmail;
                    } elseif (!$wantInactive && $current['inactivated']) {
                        $results['reactivated']++;
                        $results['messages'][] = '(↑) ' . $newEmail;
                    }
                    continue;
                }

                // --- Nouveau membre (pas d'Id, ou ligne en double) ---
                if ($validEmail === false) {
                    $fail(str_replace(['{line}', '{email}'], [(string)$line, $cellEmail], $t('import.ods.error.invalid_email')));
                    continue;
                }
                if (isset($idByEmail[$emailKey])) {
                    $fail(str_replace(
                        ['{line}', '{email}', '{id}'],
                        [(string)$line, $validEmail, (string)$idByEmail[$emailKey]],
                        $t('import.ods.error.email_exists')
                    ));
                    continue;
                }
                $newCustom = $this->applyOdsCustomFields(
                    $definitions,
                    $customMapping,
                    $cells,
                    [],
                    $line,
                    $results['messages'],
                    $t
                );
                $stmtInsertIndividual->execute([
                    ':email' => $validEmail,
                    ':firstName' => $firstName ?? '',
                    ':lastName' => $lastName ?? '',
                    ':phone' => $phone ?? '',
                ]);
                $newId = (int)$this->pdo->lastInsertId();
                $stmtInsertMember->execute([
                    ':id' => $newId,
                    // un nouveau membre est actif, sauf si la case est explicitement décochée (☐)
                    ':inactivated' => $activeCell === OdsWriter::UNCHECKED ? 1 : 0,
                    ':customFields' => MemberCustomFields::encodeValues($newCustom),
                ]);
                $idByEmail[$emailKey] = $newId;
                $results['created']++;
                $results['messages'][] = '(+) ' . $validEmail;
                if ($row['duplicateOf'] !== null) {
                    $results['messages'][] = str_replace(
                        ['{line}', '{id}'],
                        [(string)$line, (string)$row['duplicateOf']],
                        $t('import.ods.warning.duplicate_id')
                    );
                }
            }

            // Id disparus du fichier : membres désactivés
            foreach ($members as $memberId => $member) {
                if ($memberId === 1 || isset($seenIds[$memberId]) || $member['inactivated']) {
                    continue;
                }
                $stmtDeactivate->execute([':id' => $memberId]);
                $results['deactivated']++;
                $results['messages'][] = '(-) ' . $member['email'];
            }

            if ($dryRun) {
                $this->pdo->rollBack();
            } else {
                $this->pdo->commit();
            }
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $results;
    }

    /**
     * Applique les champs personnalisés d'une ligne : cellule vide = valeur effacée,
     * valeur invalide = signalée et valeur actuelle conservée, colonne absente = champ non touché.
     *
     * @param list<CustomFieldDefinition> $definitions
     * @param array<string, int> $customMapping
     * @param array<int, string> $cells
     * @param array<string, string|int|float> $current
     * @param list<string> $messages
     * @param callable(string): string $t
     * @return array<string, string|int|float>
     */
    private function applyOdsCustomFields(
        array $definitions,
        array $customMapping,
        array $cells,
        array $current,
        int $line,
        array &$messages,
        callable $t
    ): array {
        if ($customMapping === []) {
            return $current;
        }
        $extracted = MemberCustomFields::extractFromRow($definitions, $customMapping, $cells);
        $applicable = [];
        $input = [];
        foreach ($definitions as $definition) {
            if (!isset($customMapping[$definition->key])) {
                continue;
            }
            if (isset($extracted[$definition->key])) {
                $raw = $extracted[$definition->key];
                if (MemberCustomFields::normalizeValue($definition->type, $raw) === null) {
                    $messages[] = str_replace(
                        ['{line}', '{value}', '{field}'],
                        [(string)$line, $raw, $definition->label],
                        $t('import.error.invalid_custom_value')
                    );
                    continue;
                }
                $input[$definition->key] = $raw;
            }
            $applicable[] = $definition;
        }
        return MemberCustomFields::mergeValues($applicable, $current, $input);
    }

    /**
     * @param array<int, string> $headerCells
     * @param list<string> $aliases
     */
    private function findOdsColumn(array $headerCells, array $aliases): ?int
    {
        $wanted = array_map(static fn(string $alias): string => mb_strtolower(trim($alias)), $aliases);
        foreach ($headerCells as $index => $title) {
            if (in_array(mb_strtolower(trim($title)), $wanted, true)) {
                return $index;
            }
        }
        return null;
    }

    /** @return list<string> Libellé d'une clé de traduction dans toutes les langues */
    private function languageAliases(string $key): array
    {
        $stmt = $this->pdo->prepare('SELECT en_US, fr_FR, pl_PL FROM Languages WHERE Name = :name');
        $stmt->execute([':name' => $key]);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $aliases = [];
        if (is_array($row)) {
            foreach ($row as $label) {
                if (is_string($label) && $label !== '') {
                    $aliases[] = $label;
                }
            }
        }
        return $aliases;
    }

    private static function isOdsChecked(string $cell): bool
    {
        return $cell === OdsWriter::CHECKED || $cell === '✅';
    }

    /** @return list<CustomFieldDefinition> */
    private function loadCustomFieldDefinitions(): array
    {
        $stmt = $this->pdo->prepare('SELECT Value FROM Settings WHERE Name = :name');
        $stmt->execute([':name' => MemberCustomFields::SETTING_KEY]);
        $json = $stmt->fetchColumn();

        return MemberCustomFields::parseDefinitions(is_string($json) ? $json : '[]');
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

        $setClauses = ['LastSignIn = :now'];
        $params = [
            ':now'   => date('Y-m-d H:i:s'),
            ':email' => $email,
        ];
        if ($lastActivity) {
            $setClauses[] = 'LastSignOut = :lastActivity';
            $params[':lastActivity'] = $lastActivity;
        }

        $stmt = $this->pdo->prepare("
            UPDATE Member
            SET " . implode(', ', $setClauses) . "
            WHERE Id = (
                SELECT Id FROM Individual WHERE Email = :email COLLATE NOCASE
            )
        ");
        $stmt->execute($params);
    }

    /**
     * Adresse fictive déterministe : une même personne sans email reçoit toujours
     * la même adresse, ce qui évite les doublons et les désactivations à chaque réimport.
     */
    private static function fictiveEmail(string $firstName, string $lastName, string $phone, string $rawEmail): string
    {
        $seed = ($firstName !== '' || $lastName !== '')
            ? mb_strtolower($firstName . '|' . $lastName)
            : mb_strtolower($rawEmail . '|' . $phone);

        return substr(sha1($seed), 0, 32) . '@myclub.foo';
    }
}
