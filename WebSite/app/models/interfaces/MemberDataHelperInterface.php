<?php

declare(strict_types=1);

namespace app\models\interfaces;

interface MemberDataHelperInterface
{
    /**
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
    public function findForSignIn(string $email): object|false;

    /**
     * @return (object{
     *     Id: int,
     *     Email: string,
     *     FirstName: string,
     *     LastName: string|null,
     *     NickName: string|null,
     *     Avatar: string|null
     * }&\stdClass)|false
     */
    public function findByRememberToken(string $token): object|false;

    /**
     * @return (object{
     *     Id: int,
     *     Email: string,
     *     FirstName: string,
     *     LastName: string|null,
     *     NickName: string|null,
     *     Avatar: string|null
     * }&\stdClass)|false
     */
    public function findBasicByEmail(string $email): object|false;

    /**
     * @return object{Id: int, TokenCreatedAt: string|null}|false
     */
    public function findByResetToken(string $token): object|false;

    /**
     * @return list<object{Email: string}>
     */
    public function getActiveMemberEmails(): array;

    /**
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
    public function getActiveMembersBasicInfo(): array;

    public function recordSignIn(int $id): void;

    public function recordSignOutByEmail(string $email): bool;

    public function setRememberToken(int $id, string $token): void;

    public function setResetToken(int $id, string $token): void;

    public function finalizeReset(int $id, string $hashedPassword): void;
}
