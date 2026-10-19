<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use PDOStatement;
use RuntimeException;
use stdClass;
use app\helpers\Application;
use app\modules\Common\valueObjects\MemberExportRow;

/**
 * @phpstan-import-type MemberExportRowShape from MemberExportRow
 *
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
 */
final class PersonDataHelper extends Data
{
    /**
     * Common Member/Individual join fragment (aliases m/i), shared by the
     * member-related data helpers.
     */
    public const MEMBER_INDIVIDUAL_JOIN = 'FROM Member m INNER JOIN Individual i ON i.Id = m.Id';

    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * Runs a SELECT query and fails loudly (Application::unreachable) if the
     * preparation/execution fails, instead of letting a `false` silently propagate.
     */
    private function queryOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            Application::unreachable("Query failed");
        }
        return $stmt;
    }

    /**
     * Adds a conditional WHERE clause for a tri-state filter
     * (true / false / null = ignored).
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
        // An empty Individual is created first, then the associated Member
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
     * @return list<MemberExportRow>
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
            throw new RuntimeException("Unable to read the members for export");
        }
        /** @var list<MemberExportRowShape> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return array_map(
            static fn(object $row): MemberExportRow => MemberExportRow::fromStdClass($row),
            $rows
        );
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
            Application::unreachable("Webmaster email not found");
        }
        return $email;
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
}
