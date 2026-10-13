<?php

declare(strict_types=1);

namespace app\models\interfaces;

use app\modules\Common\valueObjects\IdentityRow;
use app\modules\Common\valueObjects\MemberBasicRow;
use app\modules\Common\valueObjects\ResetTokenRow;
use app\modules\Common\valueObjects\SignInRow;

interface MemberDataHelperInterface
{
    public function findForSignIn(string $email): SignInRow|false;

    public function findByRememberToken(string $token): IdentityRow|false;

    public function findBasicByEmail(string $email): IdentityRow|false;

    public function findByResetToken(string $token): ResetTokenRow|false;

    /**
     * @return list<string>
     */
    public function getActiveMemberEmails(): array;

    /**
     * @return list<MemberBasicRow>
     */
    public function getActiveMembersBasicInfo(): array;

    public function recordSignIn(int $id): void;

    public function recordSignOutByEmail(string $email): bool;

    public function setRememberToken(int $id, string $token): void;

    public function setResetToken(int $id, string $token): void;

    public function finalizeReset(int $id, string $hashedPassword): void;
}
