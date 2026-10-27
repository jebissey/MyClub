<?php

declare(strict_types=1);

namespace app\models;

use PDO;
use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\helpers\WebApp;
use app\modules\Common\valueObjects\DirectoryPersonRow;
use app\modules\Common\valueObjects\LocatedMemberRow;

/**
 * @phpstan-import-type DirectoryPersonRowShape from DirectoryPersonRow
 * @phpstan-import-type LocatedMemberRowShape from LocatedMemberRow
 */
final class MemberDirectoryDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );
    }

    /**
     * Contact details of every active member, indexed by email. Used by the clipboard
     * copy of the contacts of a filtered group/event type (EventEmailController::copyEmails()).
     *
     * @return array<string, object{Email: string, Phone: string|null, FirstName: string, LastName: string|null, NickName: string|null}>
     */
    public function getActiveMembersContactInfoByEmail(): array
    {
        $sql = '
            SELECT i.Email, i.Phone, i.FirstName, i.LastName, i.NickName
            ' . PersonDataHelper::MEMBER_INDIVIDUAL_JOIN . '
            WHERE m.Inactivated = 0
        ';
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
     * Returns the members who have a location.
     *
     * Modes:
     * - $memberIds === null && $onlyWithPublicData === false:
     *     active members who are in the presentation directory (historical behavior)
     * - $memberIds !== null:
     *     restricted to the given ids (Inactivated is ignored)
     * - $onlyWithPublicData === true:
     *     active members whose MyPublicDataInPresentationDirectory is neither null nor empty
     *
     * @param list<int>|null $memberIds Restricts the result to these members; null means no restriction
     * @param bool $onlyWithPublicData When true, returns active members that have non-empty public data
     * @return list<LocatedMemberRow>
     */
    public function getLocatedMembers(
        ?array $memberIds = null,
        bool $onlyWithPublicData = false
    ): array {
        // An empty restriction matches nobody (and "IN ()" is invalid SQL)
        if ($memberIds === []) {
            return [];
        }

        $sql = 'SELECT i.Id, i.FirstName, i.LastName, i.NickName, i.Avatar, '
            . 'm.UseGravatar, i.Email, m.Location, '
            . 'm.MyPublicDataInPresentationDirectory, '
            . 'm.InPresentationDirectory, '
            . 'm.ShowPhoneInPresentationDirectory, '
            . 'm.ShowEmailInPresentationDirectory '
            . PersonDataHelper::MEMBER_INDIVIDUAL_JOIN
            . ' WHERE m.Location IS NOT NULL';

        $params = [];

        if ($memberIds !== null) {
            $placeholders = implode(', ', array_fill(0, count($memberIds), '?'));
            $sql .= " AND i.Id IN ({$placeholders})";
            $params = $memberIds;
        } else {
            $sql .= ' AND m.Inactivated = 0';

            if ($onlyWithPublicData) {
                $sql .= ' AND m.MyPublicDataInPresentationDirectory IS NOT NULL'
                    . " AND m.MyPublicDataInPresentationDirectory != ''";
            } else {
                $sql .= ' AND m.InPresentationDirectory = 1';
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<LocatedMemberRowShape> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return array_map(
            static fn(object $row): LocatedMemberRow => LocatedMemberRow::fromStdClass($row),
            $rows
        );
    }

    /**
     * Active members who agreed to appear in the presentation directory.
     *
     * @return list<DirectoryPersonRow>
     */
    public function getPersonsForDirectory(): array
    {
        $sql = '
        SELECT
            i.Id,
            m.UseGravatar,
            i.Email,
            i.Avatar,
            i.FirstName,
            i.LastName,
            i.NickName
        ' . PersonDataHelper::MEMBER_INDIVIDUAL_JOIN . '
        WHERE m.InPresentationDirectory = 1
          AND m.Inactivated = 0
        ORDER BY i.FirstName, i.LastName
    ';
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return [];
        }

        /** @var list<DirectoryPersonRowShape> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $gravatarHandler = new GravatarHandler();

        $result = [];
        foreach ($rows as $row) {
            $userImg = WebApp::computeUserImg(
                $row->UseGravatar === 'yes',
                $row->Email,
                $row->Avatar,
                $gravatarHandler
            );
            $result[] = DirectoryPersonRow::fromStdClass($row, $userImg);
        }

        return $result;
    }

    /**
     * @return list<DirectoryPersonRow>
     */
    public function getPersonsInGroupForDirectory(int $groupId): array
    {
        $sql = '
            SELECT DISTINCT
                i.Id,
                m.UseGravatar,
                i.Email,
                i.Avatar,
                i.FirstName,
                i.LastName,
                i.NickName
            ' . PersonDataHelper::MEMBER_INDIVIDUAL_JOIN . '
            INNER JOIN MemberGroup mg ON mg.IdMember = m.Id
            WHERE mg.IdGroup = ?
              AND m.InPresentationDirectory = 1
              AND m.Inactivated = 0
            ORDER BY i.FirstName, i.LastName
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$groupId]);

        /** @var list<DirectoryPersonRowShape> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $gravatarHandler = new GravatarHandler();

        $result = [];
        foreach ($rows as $row) {
            $userImg = WebApp::computeUserImg(
                $row->UseGravatar === 'yes',
                $row->Email,
                $row->Avatar,
                $gravatarHandler
            );
            $result[] = DirectoryPersonRow::fromStdClass($row, $userImg);
        }

        return $result;
    }
}
