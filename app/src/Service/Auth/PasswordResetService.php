<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Mailer\AuthMailer;
use App\Model\Entity\AccountActionToken;
use App\Model\Entity\User;
use App\Model\Table\AccountActionAttemptsTable;
use App\Model\Table\AccountActionTokensTable;
use App\Model\Table\UsersTable;
use App\Service\Auth\Exception\InvalidOrExpiredTokenException;
use App\Service\Auth\Exception\ResendThrottledException;
use App\Service\Auth\Exception\WeakPasswordException;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\I18n\DateTime;

/**
 * SCR-OPR-213 パスワードを忘れた方.
 *
 * `requestReset()` never throws and never reveals whether the email exists
 * — the caller always renders the same "受付完了" message. Rate limiting
 * and existence checks happen internally and are only visible in the
 * `account_action_attempts` audit trail.
 */
class PasswordResetService
{
    /**
     * @param \App\Model\Table\UsersTable $usersTable Users table.
     * @param \App\Model\Table\AccountActionTokensTable $tokensTable Account action tokens table.
     * @param \App\Model\Table\AccountActionAttemptsTable $attemptsTable Account action attempts table.
     * @param \App\Service\Auth\PasswordPolicy $passwordPolicy Password policy.
     * @param \App\Mailer\AuthMailer $mailer Auth mailer.
     */
    public function __construct(
        private readonly UsersTable $usersTable,
        private readonly AccountActionTokensTable $tokensTable,
        private readonly AccountActionAttemptsTable $attemptsTable,
        private readonly PasswordPolicy $passwordPolicy,
        private readonly AuthMailer $mailer,
    ) {
    }

    /**
     * @param string $email The email address to send a reset link to, if it matches an eligible account.
     * @param string|null $ip The client IP address.
     * @param string|null $userAgent The client user agent string.
     * @return void
     */
    public function requestReset(string $email, ?string $ip, ?string $userAgent): void
    {
        $normalizedEmail = UsersTable::normalizeEmail($email);
        $purpose = AccountActionToken::PURPOSE_PASSWORD_RESET;

        /** @var \App\Model\Entity\User|null $user */
        $user = $this->usersTable->find('authable')
            ->where(['email' => $normalizedEmail])
            ->first();

        if ($user === null) {
            $this->attemptsTable->record($normalizedEmail, null, $purpose, 'request', 'not_found', $ip, $userAgent);

            return;
        }

        try {
            $this->guardAgainstThrottle($normalizedEmail, $purpose, $user->id, $ip, $userAgent);
        } catch (ResendThrottledException) {
            return;
        }

        $this->tokensTable->invalidateActive($user->id, $purpose);
        $issued = $this->tokensTable->issue($user->id, $purpose);

        $this->mailer->passwordResetEmail($user, $issued['token'])->deliver();

        $this->attemptsTable->record($normalizedEmail, $user->id, $purpose, 'request', 'sent', $ip, $userAgent);
    }

    /**
     * @throws \App\Service\Auth\Exception\InvalidOrExpiredTokenException
     * @throws \App\Service\Auth\Exception\WeakPasswordException
     */
    public function resetPassword(string $rawToken, string $newPassword, string $newPasswordConfirm): User
    {
        $token = $this->tokensTable->findValidByRawToken(
            $rawToken,
            AccountActionToken::PURPOSE_PASSWORD_RESET,
        );

        if ($token === null) {
            throw new InvalidOrExpiredTokenException();
        }

        if ($newPassword !== $newPasswordConfirm) {
            throw new WeakPasswordException(WeakPasswordException::CONFIRMATION_MISMATCH);
        }

        /** @var \App\Model\Entity\User $user */
        $user = $this->usersTable->get($token->user_id);

        $this->passwordPolicy->assertAcceptable($newPassword, $user->email);

        $hasher = new DefaultPasswordHasher([
            'hashType' => PASSWORD_ARGON2ID,
            'hashOptions' => (array)Configure::read('PasswordHasher.argon2id'),
        ]);

        $connection = $this->usersTable->getConnection();

        return $connection->transactional(function () use ($token, $user, $hasher, $newPassword): User {
            $this->tokensTable->markUsed($token);

            $user->patch([
                'password_hash' => $hasher->hash($newPassword),
                'failed_login_count' => 0,
                'locked_until' => null,
                // Security stamp: forces every other active session/device to
                // re-authenticate (see AppController::beforeFilter()).
                'sessions_invalidated_at' => DateTime::now(),
            ], ['guard' => false]);
            $this->usersTable->saveOrFail($user);

            return $user;
        });
    }

    /**
     * @throws \App\Service\Auth\Exception\ResendThrottledException
     */
    private function guardAgainstThrottle(
        string $normalizedEmail,
        string $purpose,
        int $userId,
        ?string $ip,
        ?string $userAgent,
    ): void {
        $cooldownSeconds = (int)Configure::read('AccountToken.resendCooldownSeconds');
        $dailyLimit = (int)Configure::read('AccountToken.resendDailyLimit');

        $lastSentAt = $this->attemptsTable->lastSentAt($normalizedEmail, $purpose);
        if ($lastSentAt !== null) {
            $retryAfter = $lastSentAt->addSeconds($cooldownSeconds);
            if ($retryAfter->isFuture()) {
                $this->attemptsTable->record(
                    $normalizedEmail,
                    $userId,
                    $purpose,
                    'request',
                    'throttled_cooldown',
                    $ip,
                    $userAgent,
                );

                throw new ResendThrottledException($retryAfter);
            }
        }

        $since = DateTime::now()->subDays(1);
        if ($this->attemptsTable->countSentSince($normalizedEmail, $purpose, $since) >= $dailyLimit) {
            $retryAfter = $since->addDays(1);
            $this->attemptsTable->record(
                $normalizedEmail,
                $userId,
                $purpose,
                'request',
                'throttled_daily_limit',
                $ip,
                $userAgent,
            );

            throw new ResendThrottledException($retryAfter);
        }
    }
}
