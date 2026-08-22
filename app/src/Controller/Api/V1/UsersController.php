<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Mailer\AuthMailer;
use App\Model\Table\AccountActionAttemptsTable;
use App\Model\Table\AccountActionTokensTable;
use App\Model\Table\UsersTable;
use App\Service\Auth\Exception\InvalidOrExpiredTokenException;
use App\Service\Auth\Exception\ResendThrottledException;
use App\Service\Auth\Exception\WeakPasswordException;
use App\Service\Auth\LoginAttemptService;
use App\Service\Auth\PasswordPolicy;
use App\Service\Auth\PasswordResetService;
use App\Service\Auth\RegistrationService;
use Cake\Core\Configure;
use Cake\Http\Response;

/**
 * JSON API for SCR-OPR-211〜215 (registration, email verification, login,
 * logout, forgot/reset password), consumed by the separately-deployed
 * Vue FRONT. Mirrors `App\Controller\UsersController` (the CakePHP-rendered
 * version, kept in parallel until FRONT is verified end-to-end - see the
 * FRONT/API migration plan) but returns JSON instead of HTML, and reuses
 * the exact same `App\Service\Auth\*` business logic.
 */
class UsersController extends AppController
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Authentication->addUnauthenticatedActions([
            'register',
            'resendVerification',
            'verifyEmail',
            'login',
            'forgotPassword',
            'resetPassword',
        ]);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function register(): Response
    {
        $this->request->allowMethod(['post']);

        try {
            $this->registrationService()->register(
                (array)$this->request->getData(),
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
            );

            return $this->json([
                'status' => 'pending_verification',
                'email' => (string)$this->request->getData('email'),
            ], 202);
        } catch (WeakPasswordException $e) {
            return $this->weakPasswordError($e);
        }
    }

    /**
     * @return \Cake\Http\Response
     */
    public function resendVerification(): Response
    {
        $this->request->allowMethod(['post']);

        $email = (string)$this->request->getData('email');

        try {
            $this->registrationService()->resendVerification(
                $email,
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
            );

            return $this->json(['status' => 'sent']);
        } catch (ResendThrottledException $e) {
            return $this->jsonError(
                'resend_throttled',
                __('確認メールの送信回数が上限に達しました。しばらくしてから再度お試しください。'),
                429,
                ['retryAfter' => $e->getRetryAfter()->format(DATE_ATOM)],
            );
        }
    }

    /**
     * @return \Cake\Http\Response
     */
    public function verifyEmail(): Response
    {
        $this->request->allowMethod(['post']);

        $token = (string)$this->request->getData('token');

        try {
            $user = $this->registrationService()->verifyEmail($token);
            $this->Authentication->setIdentity($user);
            $this->writeSecurityStamp($user->sessions_invalidated_at?->format(DATE_ATOM));

            return $this->json(['id' => $user->id, 'email' => $user->email]);
        } catch (InvalidOrExpiredTokenException) {
            return $this->jsonError(
                'invalid_or_expired_token',
                __('確認用のリンクが無効か、有効期限が切れています。確認メールの再送をお試しください。'),
            );
        }
    }

    /**
     * @return \Cake\Http\Response
     */
    public function login(): Response
    {
        $this->request->allowMethod(['post']);

        $email = UsersTable::normalizeEmail((string)$this->request->getData('email'));

        // Looked up independent of the Authentication identifier (which
        // uses the `loginable` finder and so never returns a locked
        // account) purely for failure-count/lockout bookkeeping.
        $user = $this->usersTable()->find()
            ->where(['email' => $email])
            ->first();

        $result = $this->Authentication->getResult();

        if ($result === null || !$result->isValid()) {
            if ($user !== null) {
                $this->loginAttemptService()->recordFailure($user);
            }

            return $this->jsonError(
                'invalid_credentials',
                __('メールアドレスまたはパスワードが正しくありません。'),
                401,
            );
        }

        if ($user === null) {
            // Unreachable in practice: a valid result guarantees the
            // `loginable` finder matched a row for this email.
            return $this->jsonError('internal_error', __('ログインに失敗しました。'), 500);
        }

        $this->loginAttemptService()->recordSuccess($user);
        $this->usersTable()->rehashPasswordIfNeeded($user, (string)$this->request->getData('password'));
        $this->writeSecurityStamp($user->sessions_invalidated_at?->format(DATE_ATOM));

        return $this->json(['id' => $user->id, 'email' => $user->email]);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function logout(): Response
    {
        $this->request->allowMethod(['post']);

        $this->Authentication->logout();
        $this->request->getSession()->destroy();

        return $this->json(['status' => 'logged_out']);
    }

    /**
     * Restores FRONT's login state after a page reload: FRONT holds no
     * identity of its own (it's a static SPA), so it asks the API whether
     * the session cookie it's carrying is still valid.
     *
     * @return \Cake\Http\Response
     */
    public function me(): Response
    {
        $this->request->allowMethod(['get']);

        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->jsonError('unauthenticated', __('ログインしていません。'), 401);
        }

        return $this->json(['id' => $identity->getIdentifier(), 'email' => $identity['email']]);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function forgotPassword(): Response
    {
        $this->request->allowMethod(['post']);

        $this->passwordResetService()->requestReset(
            (string)$this->request->getData('email'),
            $this->request->clientIp(),
            $this->request->getHeaderLine('User-Agent'),
        );

        return $this->json(['status' => 'accepted']);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function resetPassword(): Response
    {
        $this->request->allowMethod(['post']);

        try {
            $this->passwordResetService()->resetPassword(
                (string)$this->request->getData('token'),
                (string)$this->request->getData('password'),
                (string)$this->request->getData('password_confirm'),
            );

            return $this->json(['status' => 'reset']);
        } catch (InvalidOrExpiredTokenException) {
            return $this->jsonError(
                'invalid_or_expired_token',
                __('再設定用のリンクが無効か、有効期限が切れています。パスワード再設定をやり直してください。'),
            );
        } catch (WeakPasswordException $e) {
            return $this->weakPasswordError($e);
        }
    }

    /**
     * @param string|null $stamp The current `sessions_invalidated_at` value, or null.
     * @return void
     */
    private function writeSecurityStamp(?string $stamp): void
    {
        // A distinct top-level session key: writing this under 'Auth'
        // would collide with the Authentication plugin's own
        // SessionAuthenticator storage key and silently break login -
        // see App\Controller\UsersController for the incident this
        // guards against.
        $this->request->getSession()->write('AuthSecurityStamp', $stamp);
    }

    /**
     * @param \App\Service\Auth\Exception\WeakPasswordException $e The password-policy violation.
     * @return \Cake\Http\Response
     */
    private function weakPasswordError(WeakPasswordException $e): Response
    {
        $minLength = (string)Configure::read('PasswordPolicy.minLength');
        $maxLength = (string)Configure::read('PasswordPolicy.maxLength');

        $messages = [
            WeakPasswordException::TOO_SHORT => __('パスワードは{0}文字以上で入力してください。', $minLength),
            WeakPasswordException::TOO_LONG => __('パスワードは{0}文字以内で入力してください。', $maxLength),
            WeakPasswordException::INVALID_CHARSET => __('パスワードに使用できるのは半角英数字と記号 !#$%&|_- のみです。'),
            WeakPasswordException::DERIVED_FROM_EMAIL => __('メールアドレスから推測できるパスワードは使用できません。'),
            WeakPasswordException::SEQUENTIAL_OR_REPEATED => __('単純な連番や同一文字だけのパスワードは使用できません。'),
            WeakPasswordException::CONFIRMATION_MISMATCH => __('確認用パスワードが一致しません。'),
        ];

        $message = $messages[$e->getReasonCode()] ?? __('入力されたパスワードは使用できません。');

        return $this->jsonError('weak_password', $message, 422, ['reasonCode' => $e->getReasonCode()]);
    }

    /**
     * @return \App\Service\Auth\RegistrationService
     */
    private function registrationService(): RegistrationService
    {
        return new RegistrationService(
            $this->usersTable(),
            $this->accountActionTokensTable(),
            $this->accountActionAttemptsTable(),
            new PasswordPolicy(),
            new AuthMailer(),
        );
    }

    /**
     * @return \App\Service\Auth\PasswordResetService
     */
    private function passwordResetService(): PasswordResetService
    {
        return new PasswordResetService(
            $this->usersTable(),
            $this->accountActionTokensTable(),
            $this->accountActionAttemptsTable(),
            new PasswordPolicy(),
            new AuthMailer(),
        );
    }

    /**
     * @return \App\Service\Auth\LoginAttemptService
     */
    private function loginAttemptService(): LoginAttemptService
    {
        return new LoginAttemptService($this->usersTable());
    }

    /**
     * @return \App\Model\Table\AccountActionTokensTable
     */
    private function accountActionTokensTable(): AccountActionTokensTable
    {
        /** @var \App\Model\Table\AccountActionTokensTable */
        return $this->fetchTable('AccountActionTokens');
    }

    /**
     * @return \App\Model\Table\AccountActionAttemptsTable
     */
    private function accountActionAttemptsTable(): AccountActionAttemptsTable
    {
        /** @var \App\Model\Table\AccountActionAttemptsTable */
        return $this->fetchTable('AccountActionAttempts');
    }
}
