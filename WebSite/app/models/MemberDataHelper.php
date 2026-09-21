<?php

declare(strict_types=1);

namespace app\models;

use DateTime;
use PDO;
use app\helpers\Application;

/**
 * Data access for sign-in, remember-me and password reset flows.
 * Hides the Individual/Member split and column names from callers
 * (especially AuthenticationService).
 */
class MemberDataHelper extends Data
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
     *
     * @return (object{
     *     Id: int,
     *     Email: string,
     *     FirstName: string,
     *     LastName: string|null,
     *     NickName: string|null,
     *     Avatar: string|null,
     *     Password: string|null,
     *     Inactivated: mixed,
     *     UseGravatar: mixed,
     *     Alert: mixed
     * }&\stdClass)|false
     */
    public function findForSignIn(string $email): object|false
    {
        /** @var object{Id: int, Email: string, FirstName: string, LastName: string|null, NickName: string|null, Avatar: string|null}|false $individual */
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

        /** @var object{Id: int, Email: string, FirstName: string, LastName: string|null, NickName: string|null, Avatar: string|null, Password: string|null, Inactivated: mixed, UseGravatar: mixed, Alert: mixed}&\stdClass $row */
        $row = (object) array_merge((array) $individual, (array) $member);

        return $row;
    }

    /**
     * Merged Individual + Member row from a remember-me token.
     *
     * @return (object{
     *     Id: int,
     *     Email: string,
     *     FirstName: string,
     *     LastName: string|null,
     *     NickName: string|null,
     *     Avatar: string|null
     * }&\stdClass)|false
     */
    public function findByRememberToken(string $token): object|false
    {
        /** @var object{Id: int, Inactivated: mixed}|false $member */
        $member = $this->get('Member', ['Token' => $token], 'Id, Inactivated');
        if ($member === false || (bool) ($member->Inactivated ?? false)) {
            return false;
        }

        /** @var object{Id: int, Email: string, FirstName: string, LastName: string|null, NickName: string|null, Avatar: string|null}|false $individual */
        $individual = $this->get(
            'Individual',
            ['Id' => $member->Id],
            'Id, Email, FirstName, LastName, NickName, Avatar'
        );
        if ($individual === false) {
            return false;
        }

        /** @var object{Id: int, Email: string, FirstName: string, LastName: string|null, NickName: string|null, Avatar: string|null}&\stdClass $row */
        $row = (object) array_merge((array) $individual, (array) $member);

        return $row;
    }

    /**
     * Merged Individual + Member row (without Password), for the reset-password email.
     *
     * @return (object{
     *     Id: int,
     *     Email: string,
     *     FirstName: string,
     *     LastName: string|null,
     *     NickName: string|null,
     *     Avatar: string|null
     * }&\stdClass)|false
     */
    public function findBasicByEmail(string $email): object|false
    {
        /** @var object{Id: int, Email: string, FirstName: string, LastName: string|null, NickName: string|null, Avatar: string|null}|false $individual */
        $individual = $this->get(
            'Individual',
            ['Email' => $email],
            'Id, Email, FirstName, LastName, NickName, Avatar'
        );
        if ($individual === false) {
            return false;
        }

        /** @var object{UseGravatar: mixed, Alert: mixed}|false $member */
        $member = $this->get('Member', ['Id' => $individual->Id], 'UseGravatar, Alert');
        if ($member === false) {
            return false;
        }

        /** @var object{Id: int, Email: string, FirstName: string, LastName: string|null, NickName: string|null, Avatar: string|null}&\stdClass $row */
        $row = (object) array_merge((array) $individual, (array) $member);

        return $row;
    }

    /**
     * Member row identified by a password-reset token.
     *
     * @return object{Id: int, TokenCreatedAt: string|null}|false
     */
    public function findByResetToken(string $token): object|false
    {
        /** @var object{Id: int, TokenCreatedAt: string|null}|false $row */
        $row = $this->get('Member', ['Token' => $token], 'Id, TokenCreatedAt');

        return $row;
    }

    /**
     * Email of every active (non-inactivated) member, for statistics aggregation.
     *
     * @return list<object{Email: string}>
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
        return array_values($stmt->fetchAll(PDO::FETCH_OBJ));
    }

    /**
     * Basic identity + presence fields for every active (non-inactivated) member,
     * used by the "who's online" chat feature (ChatApi::getActiveUsers).
     *
     * @return list<object{
     *     Id: int,
     *     NickName: string|null,
     *     FirstName: string,
     *     LastName: string|null,
     *     Email: string,
     *     Avatar: string|null,
     *     UseGravatar: mixed
     * }&\stdClass>
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
        return array_values($stmt->fetchAll(PDO::FETCH_OBJ));
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
