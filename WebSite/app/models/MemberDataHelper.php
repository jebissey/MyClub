<?php

declare(strict_types=1);

namespace app\models;

use DateTime;
use PDO;
use app\helpers\Application;
use app\helpers\To;
use app\modules\Common\valueObjects\IdentityRow;
use app\modules\Common\valueObjects\LocatedMemberRow;
use app\modules\Common\valueObjects\MemberBasicRow;
use app\modules\Common\valueObjects\ResetTokenRow;
use app\modules\Common\valueObjects\SignInRow;

/**
 * Data access for sign-in, remember-me and password reset flows.
 * Hides the Individual/Member split and column names from callers
 * (especially AuthenticationService).
 *
 * @phpstan-import-type LocatedMemberRowShape from LocatedMemberRow
 * @phpstan-import-type IndividualShape from IdentityRow
 * @phpstan-import-type JoinedShape from MemberBasicRow
 */
final class MemberDataHelper extends Data
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
     * Merged Individual + Member row, including Password/Inactivated for authentication.
     */
    public function findForSignIn(string $email): SignInRow|false
    {
        /** @var IndividualShape|false $individual */
        $individual = $this->get(
            'Individual',
            ['Email' => $email],
            'Id, Email, FirstName, LastName, NickName, Avatar'
        );
        if ($individual === false) {
            return false;
        }

        /** @var object{Password: string|null, Inactivated: mixed, UseGravatar: mixed, Alert: mixed}|false $member */
        $member = $this->get(
            'Member',
            ['Id' => $individual->Id],
            'Password, Inactivated, UseGravatar, Alert'
        );
        if ($member === false) {
            return false;
        }

        return SignInRow::fromRows($individual, $member);
    }

    /**
     * Identity row of an active member, looked up from a remember-me token.
     */
    public function findByRememberToken(string $token): IdentityRow|false
    {
        /** @var object{Id: int, Inactivated: mixed}|false $member */
        $member = $this->get('Member', ['Token' => $token], 'Id, Inactivated');
        if ($member === false || (bool) $member->Inactivated) {
            return false;
        }

        /** @var IndividualShape|false $individual */
        $individual = $this->get(
            'Individual',
            ['Id' => $member->Id],
            'Id, Email, FirstName, LastName, NickName, Avatar'
        );

        return $individual === false ? false : IdentityRow::fromIndividual($individual);
    }

    /**
     * Identity row (without Password), for the reset-password email.
     * Returns false if the Individual has no matching Member row.
     */
    public function findBasicByEmail(string $email): IdentityRow|false
    {
        /** @var IndividualShape|false $individual */
        $individual = $this->get(
            'Individual',
            ['Email' => $email],
            'Id, Email, FirstName, LastName, NickName, Avatar'
        );
        if ($individual === false) {
            return false;
        }

        if ($this->get('Member', ['Id' => $individual->Id], 'Id') === false) {
            return false;
        }

        return IdentityRow::fromIndividual($individual);
    }

    /**
     * Member identified by a password-reset token.
     */
    public function findByResetToken(string $token): ResetTokenRow|false
    {
        /** @var object{Id: int, TokenCreatedAt: string|null}|false $row */
        $row = $this->get('Member', ['Token' => $token], 'Id, TokenCreatedAt');

        return $row === false ? false : ResetTokenRow::fromRow($row);
    }

    /**
     * Email of every active (non-inactivated) member, for statistics aggregation.
     *
     * @return list<string>
     */
    public function getActiveMemberEmails(): array
    {
        $sql = "
            SELECT Individual.Email
            FROM Individual
            INNER JOIN Member ON Member.Id = Individual.Id
            WHERE Member.Inactivated = 0
        ";
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return [];
        }

        return array_values(
            array_map(
                static fn(mixed $email): string => To::str($email),
                $stmt->fetchAll(PDO::FETCH_COLUMN)
            )
        );
    }

    /**
     * Basic identity + presence fields for every active (non-inactivated) member,
     * used by the "who's online" chat feature (ChatApi::getActiveUsers).
     *
     * @return list<MemberBasicRow>
     */
    public function getActiveMembersBasicInfo(): array
    {
        $sql = "
            SELECT
                Individual.Id,
                Individual.NickName,
                Individual.FirstName,
                Individual.LastName,
                Individual.Email,
                Individual.Avatar,
                Member.UseGravatar
            FROM Member
            INNER JOIN Individual ON Individual.Id = Member.Id
            WHERE Member.Inactivated = 0
        ";
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return [];
        }

        /** @var list<JoinedShape> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return array_map(MemberBasicRow::fromJoinedRow(...), $rows);
    }

    public function recordSignIn(int $id): void
    {
        $this->set('Member', ['LastSignIn' => date('Y-m-d H:i:s')], ['Id' => $id]);
    }

    /**
     * @return bool true if a matching individual was found and updated
     */
    public function recordSignOutByEmail(string $email): bool
    {
        /** @var object{Id: int}|false $individual */
        $individual = $this->get('Individual', ['Email' => $email], 'Id');
        if ($individual === false) {
            return false;
        }

        $this->set('Member', ['LastSignOut' => date('Y-m-d H:i:s')], ['Id' => $individual->Id]);

        return true;
    }

    /**
     * Directory information for every active member who agreed
     * to appear in the presentation directory.
     *
     * @return list<LocatedMemberRow>
     */
    public function getActiveMembersDirectoryInfo(): array
    {
        $sql = "
            SELECT
                Individual.Id,
                Individual.LastName,
                Individual.FirstName,
                Individual.NickName,
                Individual.Avatar,
                Individual.Email,
                Member.UseGravatar,
                Member.InPresentationDirectory,
                Member.ShowPhoneInPresentationDirectory,
                Member.ShowEmailInPresentationDirectory,
                Member.Location,
                NULL as MyPublicDataInPresentationDirectory
            FROM Member
            INNER JOIN Individual ON Individual.Id = Member.Id
            WHERE Member.InPresentationDirectory = 1
            AND Member.Inactivated = 0
            ORDER BY Individual.FirstName, Individual.LastName
        ";
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return [];
        }

        /** @var list<LocatedMemberRowShape> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        return array_map(
            static fn(object $row): LocatedMemberRow => LocatedMemberRow::fromStdClass($row),
            $rows
        );
    }

    public function setRememberToken(int $id, string $token): void
    {
        $this->set('Member', ['Token' => $token], ['Id' => $id]);
    }

    public function setResetToken(int $id, string $token): void
    {
        $this->set(
            'Member',
            [
                'Token'          => $token,
                'TokenCreatedAt' => (new DateTime())->format('Y-m-d H:i:s'),
            ],
            ['Id' => $id]
        );
    }

    public function finalizeReset(int $id, string $hashedPassword): void
    {
        $this->set(
            'Member',
            [
                'Password'       => $hashedPassword,
                'Token'          => null,
                'TokenCreatedAt' => null,
            ],
            ['Id' => $id]
        );
    }
}
