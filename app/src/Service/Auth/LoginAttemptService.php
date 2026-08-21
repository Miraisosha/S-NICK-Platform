<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use App\Service\Auth\Exception\AccountLockedException;
use Cake\Core\Configure;
use Cake\I18n\DateTime;

/**
 * Login failure counting and lockout, per SCR-OPR-214:
 * "同一アカウントで連続5回ログインに失敗した場合は、30分間ログインを停止する"
 * and "停止中に...さらに失敗した場合は...延長する".
 */
class LoginAttemptService
{
    /**
     * @param \App\Model\Table\UsersTable $usersTable Users table.
     */
    public function __construct(private readonly UsersTable $usersTable)
    {
    }

    /**
     * @throws \App\Service\Auth\Exception\AccountLockedException
     */
    public function guardAgainstLockout(User $user): void
    {
        if ($user->isLocked()) {
            throw new AccountLockedException();
        }
    }

    /**
     * @param \App\Model\Entity\User $user The user who just failed to authenticate.
     * @return void
     */
    public function recordFailure(User $user): void
    {
        $maxFailures = (int)Configure::read('LoginLockout.maxFailures');
        $lockMinutes = (int)Configure::read('LoginLockout.lockMinutes');

        $user->failed_login_count += 1;

        if ($user->failed_login_count >= $maxFailures || $user->isLocked()) {
            $user->locked_until = DateTime::now()->addMinutes($lockMinutes);
        }

        $this->usersTable->save($user, ['checkRules' => false, 'validate' => false]);
    }

    /**
     * @param \App\Model\Entity\User $user The user who just authenticated successfully.
     * @return void
     */
    public function recordSuccess(User $user): void
    {
        $user->failed_login_count = 0;
        $user->locked_until = null;

        $this->usersTable->save($user, ['checkRules' => false, 'validate' => false]);
    }
}
