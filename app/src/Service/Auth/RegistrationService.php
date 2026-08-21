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
 * SCR-OPR-211 ユーザー登録 / SCR-OPR-212 メール確認結果.
 *
 * The public entry point `register()` always results in the same generic
 * "確認メールをご確認ください" outcome for the caller regardless of whether
 * the email is new, already pending, previously soft-deleted, or already
 * active — only the internal side effects differ. This is a deliberately
 * stricter anti-enumeration posture than the spec's literal text (which
 * only mandates a generic message for the forgot-password flow); see the
 * plan's judgment call #4.
 */
class RegistrationService
{
    /**
     * Placeholder pending the platform's actual terms-of-service versioning
     * scheme, which has not been decided yet.
     */
    public const CURRENT_TERMS_VERSION = '2026-08-01';

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
     * @param array<string, mixed> $data Submitted form data; expected keys are
     *   `email`, `password` and `password_confirm`.
     * @param string|null $ip The client IP address.
     * @param string|null $userAgent The client user agent string.
     * @return void
     * @throws \App\Service\Auth\Exception\WeakPasswordException
     */
    public function register(array $data, ?string $ip, ?string $userAgent): void
    {
        $normalizedEmail = UsersTable::normalizeEmail((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $passwordConfirm = (string)($data['password_confirm'] ?? '');

        if ($password !== $passwordConfirm) {
            throw new WeakPasswordException(WeakPasswordException::CONFIRMATION_MISMATCH);
        }

        $this->passwordPolicy->assertAcceptable($password, $normalizedEmail);

        /** @var \App\Model\Entity\User|null $existing */
        $existing = $this->usersTable->find()
            ->where(['email' => $normalizedEmail])
            ->first();

        if ($existing === null) {
            $user = $this->createUser($normalizedEmail, $password);
            $this->issueAndSendVerification($user, $ip, $userAgent, 'register');

            return;
        }

        if ($existing->deleted_at !== null) {
            $user = $this->reactivateUser($existing, $password);
            $this->issueAndSendVerification($user, $ip, $userAgent, 'reactivate');

            return;
        }

        if ($existing->email_verified_at === null) {
            // Duplicate submission against a still-pending registration:
            // treat as a resend rather than creating a new row.
            try {
                $this->issueAndSendVerification($existing, $ip, $userAgent, 'resend');
            } catch (ResendThrottledException) {
                // register() never surfaces throttle details to the caller;
                // the attempt itself was already recorded by issueAndSendVerification().
            }

            return;
        }

        // Already active and verified: no email is sent, but the attempt is
        // still logged for audit purposes. The caller's response is
        // identical to every other branch above.
        $this->attemptsTable->record(
            $normalizedEmail,
            $existing->id,
            AccountActionToken::PURPOSE_EMAIL_VERIFICATION,
            'register',
            'already_verified',
            $ip,
            $userAgent,
        );
    }

    /**
     * @throws \App\Service\Auth\Exception\ResendThrottledException
     */
    public function resendVerification(string $email, ?string $ip, ?string $userAgent): void
    {
        $normalizedEmail = UsersTable::normalizeEmail($email);

        /** @var \App\Model\Entity\User|null $user */
        $user = $this->usersTable->find('active')
            ->where(['email' => $normalizedEmail, 'email_verified_at IS' => null])
            ->first();

        if ($user === null) {
            $this->attemptsTable->record(
                $normalizedEmail,
                null,
                AccountActionToken::PURPOSE_EMAIL_VERIFICATION,
                'resend',
                'not_found',
                $ip,
                $userAgent,
            );

            return;
        }

        $this->issueAndSendVerification($user, $ip, $userAgent, 'resend');
    }

    /**
     * @throws \App\Service\Auth\Exception\InvalidOrExpiredTokenException
     */
    public function verifyEmail(string $rawToken): User
    {
        $token = $this->tokensTable->findValidByRawToken(
            $rawToken,
            AccountActionToken::PURPOSE_EMAIL_VERIFICATION,
        );

        if ($token === null) {
            throw new InvalidOrExpiredTokenException();
        }

        return $this->usersTable->getConnection()->transactional(function () use ($token): User {
            $this->tokensTable->markUsed($token);

            /** @var \App\Model\Entity\User $user */
            $user = $this->usersTable->get($token->user_id);
            $user->set('email_verified_at', DateTime::now(), ['guard' => false]);
            $this->usersTable->saveOrFail($user);

            return $user;
        });
    }

    /**
     * @throws \App\Service\Auth\Exception\ResendThrottledException
     */
    private function issueAndSendVerification(User $user, ?string $ip, ?string $userAgent, string $action): void
    {
        $normalizedEmail = $user->email;
        $purpose = AccountActionToken::PURPOSE_EMAIL_VERIFICATION;

        $this->guardAgainstThrottle($normalizedEmail, $purpose, $user->id, $action, $ip, $userAgent);

        $this->tokensTable->invalidateActive($user->id, $purpose);
        $issued = $this->tokensTable->issue($user->id, $purpose);

        $this->mailer->verificationEmail($user, $issued['token'])->deliver();

        $this->attemptsTable->record($normalizedEmail, $user->id, $purpose, $action, 'sent', $ip, $userAgent);
    }

    /**
     * @throws \App\Service\Auth\Exception\ResendThrottledException
     */
    private function guardAgainstThrottle(
        string $normalizedEmail,
        string $purpose,
        ?int $userId,
        string $action,
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
                    $action,
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
                $action,
                'throttled_daily_limit',
                $ip,
                $userAgent,
            );

            throw new ResendThrottledException($retryAfter);
        }
    }

    /**
     * @param string $normalizedEmail Already-normalized email address.
     * @param string $password Plain-text password to hash and store.
     * @return \App\Model\Entity\User
     */
    private function createUser(string $normalizedEmail, string $password): User
    {
        $hasher = new DefaultPasswordHasher([
            'hashType' => PASSWORD_ARGON2ID,
            'hashOptions' => (array)Configure::read('PasswordHasher.argon2id'),
        ]);

        /** @var \App\Model\Entity\User $user */
        $user = $this->usersTable->newEntity([
            'account_number' => bin2hex(random_bytes(8)),
            'email' => $normalizedEmail,
            'password_hash' => $hasher->hash($password),
            'status' => 'active',
            'terms_agreed_at' => DateTime::now(),
            'terms_version' => self::CURRENT_TERMS_VERSION,
        ], ['accessibleFields' => ['*' => true]]);

        $this->usersTable->saveOrFail($user);

        // Replace the temporary unique placeholder with a stable,
        // human-referenceable account number derived from the row id
        // (format undecided by spec; flagged in the implementation plan).
        $user->set('account_number', sprintf('U%010d', $user->id), ['guard' => false]);
        $this->usersTable->saveOrFail($user);

        return $user;
    }

    /**
     * @param \App\Model\Entity\User $user The soft-deleted user row to reactivate.
     * @param string $password Plain-text password to hash and store.
     * @return \App\Model\Entity\User
     */
    private function reactivateUser(User $user, string $password): User
    {
        $hasher = new DefaultPasswordHasher([
            'hashType' => PASSWORD_ARGON2ID,
            'hashOptions' => (array)Configure::read('PasswordHasher.argon2id'),
        ]);

        $user->patch([
            'password_hash' => $hasher->hash($password),
            'deleted_at' => null,
            'email_verified_at' => null,
            'failed_login_count' => 0,
            'locked_until' => null,
            'terms_agreed_at' => DateTime::now(),
            'terms_version' => self::CURRENT_TERMS_VERSION,
        ], ['guard' => false]);

        $this->usersTable->saveOrFail($user);

        return $user;
    }
}
