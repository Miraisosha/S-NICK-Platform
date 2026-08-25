<?php
declare(strict_types=1);

namespace App\Controller;

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
use RuntimeException;

/**
 * SCR-OPR-211〜215: ユーザー登録・メール確認・ログイン・パスワード再設定.
 *
 * Deliberately out of scope for this controller (see the implementation
 * plan): multi-device session listing/management, 30-day auto-login
 * tokens, two-factor authentication, and OPR-220 profile/email/password
 * change screens.
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
     * SCR-OPR-211 ユーザー登録.
     *
     * @return \Cake\Http\Response|null
     */
    public function register(): ?Response
    {
        if ($this->request->is('post')) {
            try {
                $this->registrationService()->register(
                    (array)$this->request->getData(),
                    $this->request->clientIp(),
                    $this->request->getHeaderLine('User-Agent'),
                );

                $this->set('email', (string)$this->request->getData('email'));

                return $this->render('register_pending');
            } catch (WeakPasswordException $e) {
                $this->Flash->error($this->passwordErrorMessage($e));
            }
        }

        return null;
    }

    /**
     * SCR-OPR-211: dedicated resend button on the "confirmation pending" screen.
     *
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
            $this->Flash->success(__('確認メールを再送しました。'));
        } catch (ResendThrottledException $e) {
            $this->Flash->error(__(
                '確認メールの送信回数が上限に達しました。{0}以降に再度お試しください。',
                $e->getRetryAfter()->i18nFormat('yyyy-MM-dd HH:mm'),
            ));
        }

        $this->set('email', $email);

        return $this->render('register_pending');
    }

    /**
     * SCR-OPR-212 メール確認結果.
     *
     * @return \Cake\Http\Response|null
     */
    public function verifyEmail(): ?Response
    {
        $token = (string)$this->request->getQuery('token');

        try {
            $user = $this->registrationService()->verifyEmail($token);
            $this->Authentication->setIdentity($user);
            $this->Flash->success(__('メールアドレスの確認が完了しました。'));

            return $this->redirect('/dashboard');
        } catch (InvalidOrExpiredTokenException) {
            $this->Flash->error(__('確認用のリンクが無効か、有効期限が切れています。確認メールの再送をお試しください。'));

            return $this->redirect(['action' => 'register']);
        }
    }

    /**
     * SCR-OPR-214 ログイン認証.
     *
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        if ($this->request->is('post')) {
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
                $this->Flash->error(__('メールアドレスまたはパスワードが正しくありません。'));

                return null;
            }

            if ($user === null) {
                // Unreachable in practice: a valid result guarantees the
                // `loginable` finder matched a row for this email.
                throw new RuntimeException('Authenticated identity has no matching user row.');
            }

            $this->loginAttemptService()->recordSuccess($user);
            $this->usersTable()->rehashPasswordIfNeeded($user, (string)$this->request->getData('password'));

            // A distinct top-level session key: writing this under 'Auth'
            // (even as 'Auth.securityStamp') would collide with the
            // Authentication plugin's own SessionAuthenticator, which
            // stores the persisted identity directly under 'Auth' and
            // skips writing it if that key already exists - silently
            // breaking login entirely.
            $this->request->getSession()->write(
                'AuthSecurityStamp',
                $user->sessions_invalidated_at?->format(DATE_ATOM),
            );

            return $this->redirect($this->safeRedirectTarget());
        }

        return null;
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function logout(): ?Response
    {
        $this->request->allowMethod(['post']);

        $this->Authentication->logout();
        $this->request->getSession()->destroy();

        return $this->redirect(['action' => 'login']);
    }

    /**
     * SCR-OPR-213 パスワードを忘れた方.
     *
     * @return \Cake\Http\Response|null
     */
    public function forgotPassword(): ?Response
    {
        if ($this->request->is('post')) {
            $this->passwordResetService()->requestReset(
                (string)$this->request->getData('email'),
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
            );

            return $this->render('forgot_password_sent');
        }

        return null;
    }

    /**
     * SCR-OPR-213: new-password form + submission.
     *
     * @return \Cake\Http\Response|null
     */
    public function resetPassword(): ?Response
    {
        $token = (string)($this->request->getData('token') ?: $this->request->getQuery('token'));
        $this->set('token', $token);

        if ($this->request->is('post')) {
            try {
                $this->passwordResetService()->resetPassword(
                    $token,
                    (string)$this->request->getData('password'),
                    (string)$this->request->getData('password_confirm'),
                );

                return $this->render('reset_password_complete');
            } catch (InvalidOrExpiredTokenException) {
                $this->Flash->error(__('再設定用のリンクが無効か、有効期限が切れています。パスワード再設定をやり直してください。'));

                return $this->redirect(['action' => 'forgotPassword']);
            } catch (WeakPasswordException $e) {
                $this->Flash->error($this->passwordErrorMessage($e));
            }
        }

        return null;
    }

    /**
     * The post-login redirect target (`?redirect=`) must stay within this
     * site: only accept an internal, root-relative path.
     */
    private function safeRedirectTarget(): string
    {
        $target = (string)$this->request->getQuery('redirect', '/dashboard');

        if ($target === '' || $target[0] !== '/' || str_starts_with($target, '//')) {
            return '/dashboard';
        }

        return $target;
    }

    /**
     * @param \App\Service\Auth\Exception\WeakPasswordException $e The password-policy violation.
     * @return string
     */
    private function passwordErrorMessage(WeakPasswordException $e): string
    {
        return match ($e->getReasonCode()) {
            WeakPasswordException::TOO_SHORT => __(
                'パスワードは{0}文字以上で入力してください。',
                (string)Configure::read('PasswordPolicy.minLength'),
            ),
            WeakPasswordException::TOO_LONG => __(
                'パスワードは{0}文字以内で入力してください。',
                (string)Configure::read('PasswordPolicy.maxLength'),
            ),
            WeakPasswordException::INVALID_CHARSET => __(
                'パスワードに使用できるのは半角英数字と記号 !#$%&|_- のみです。',
            ),
            WeakPasswordException::DERIVED_FROM_EMAIL => __(
                'メールアドレスから推測できるパスワードは使用できません。',
            ),
            WeakPasswordException::SEQUENTIAL_OR_REPEATED => __(
                '単純な連番や同一文字だけのパスワードは使用できません。',
            ),
            WeakPasswordException::CONFIRMATION_MISMATCH => __(
                '確認用パスワードが一致しません。',
            ),
            default => __('入力されたパスワードは使用できません。'),
        };
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
