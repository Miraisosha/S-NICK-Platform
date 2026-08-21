<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Entity\AccountActionToken;
use App\Model\Entity\User;
use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private $Users;
    private $Tokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Users = $this->fetchTable('Users');
        $this->Tokens = $this->fetchTable('AccountActionTokens');

        $this->enableCsrfToken();
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
        unset($this->Users, $this->Tokens);
        parent::tearDown();
    }

    private function makeVerifiedUser(string $email, string $password = 'Xk7!qpLm'): User
    {
        $user = $this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'email_verified_at' => DateTime::now(),
        ], ['accessibleFields' => ['*' => true]]);

        return $this->Users->saveOrFail($user);
    }

    // --- Registration -----------------------------------------------------

    public function testRegisterCreatesUnverifiedUser(): void
    {
        $email = 'register-' . bin2hex(random_bytes(4)) . '@example.com';

        $this->post('/users/register', [
            'email' => $email,
            'password' => 'Xk7!qpLm',
            'password_confirm' => 'Xk7!qpLm',
            'terms_agreed' => '1',
        ]);

        $this->assertResponseOk();
        $user = $this->Users->find()->where(['email' => $email])->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
    }

    public function testRegisterWithWeakPasswordShowsErrorAndCreatesNoUser(): void
    {
        $email = 'weak-' . bin2hex(random_bytes(4)) . '@example.com';

        $this->post('/users/register', [
            'email' => $email,
            'password' => 'abc',
            'password_confirm' => 'abc',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('パスワードは6文字以上で入力してください。');
        $this->assertNull($this->Users->find()->where(['email' => $email])->first());
    }

    // --- Email verification ------------------------------------------------

    public function testVerifyEmailActivatesAccountAndRedirects(): void
    {
        $email = 'verify-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->Users->saveOrFail($this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => $email,
            'password_hash' => password_hash('Xk7!qpLm', PASSWORD_ARGON2ID),
        ], ['accessibleFields' => ['*' => true]]));

        $issued = $this->Tokens->issue($user->id, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);

        $this->get('/users/verify-email?token=' . $issued['token']);

        $this->assertResponseSuccess();
        $reloaded = $this->Users->get($user->id);
        $this->assertNotNull($reloaded->email_verified_at);
    }

    public function testVerifyEmailWithInvalidTokenRedirectsToRegister(): void
    {
        $this->get('/users/verify-email?token=not-a-real-token');

        $this->assertRedirectContains('/users/register');
        $this->assertFlashMessage('確認用のリンクが無効か、有効期限が切れています。確認メールの再送をお試しください。');
    }

    // --- Login ---------------------------------------------------------------

    public function testLoginWithValidCredentialsRedirects(): void
    {
        $email = 'login-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeVerifiedUser($email);

        $this->post('/users/login', ['email' => $email, 'password' => 'Xk7!qpLm']);

        $this->assertResponseSuccess();
        $this->assertRedirect('/');
    }

    public function testLoginWithWrongPasswordShowsGenericMessage(): void
    {
        $email = 'wrongpw-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeVerifiedUser($email);

        $this->post('/users/login', ['email' => $email, 'password' => 'WrongPass1!']);

        $this->assertResponseOk();
        $this->assertResponseContains('メールアドレスまたはパスワードが正しくありません。');
    }

    public function testLoginWithUnknownEmailShowsSameGenericMessage(): void
    {
        $this->post('/users/login', [
            'email' => 'nobody-' . bin2hex(random_bytes(4)) . '@example.com',
            'password' => 'WrongPass1!',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('メールアドレスまたはパスワードが正しくありません。');
    }

    public function testFailedLoginIncrementsFailureCount(): void
    {
        $email = 'count-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);

        $this->post('/users/login', ['email' => $email, 'password' => 'WrongPass1!']);

        $reloaded = $this->Users->get($user->id);
        $this->assertSame(1, $reloaded->failed_login_count);
    }

    public function testAccountLocksAfterFiveConsecutiveFailures(): void
    {
        $email = 'lockout-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/users/login', ['email' => $email, 'password' => 'WrongPass1!']);
        }

        $reloaded = $this->Users->get($user->id);
        $this->assertTrue($reloaded->isLocked());

        // Correct credentials are still rejected while locked, with the same
        // generic message (no account-existence/lock-state leak).
        $this->post('/users/login', ['email' => $email, 'password' => 'Xk7!qpLm']);
        $this->assertResponseContains('メールアドレスまたはパスワードが正しくありません。');
    }

    public function testLockoutExtendsOnFailureWhileAlreadyLocked(): void
    {
        $email = 'extend-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/users/login', ['email' => $email, 'password' => 'WrongPass1!']);
        }
        $lockedAt = $this->Users->get($user->id)->locked_until;

        Chronos::setTestNow(DateTime::now()->addMinutes(10));
        $this->post('/users/login', ['email' => $email, 'password' => 'WrongPass1!']);

        $extendedUntil = $this->Users->get($user->id)->locked_until;
        $this->assertTrue($extendedUntil->greaterThan($lockedAt));
    }

    public function testLoginSucceedsAfterLockoutExpires(): void
    {
        $email = 'unlock-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeVerifiedUser($email);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/users/login', ['email' => $email, 'password' => 'WrongPass1!']);
        }

        Chronos::setTestNow(DateTime::now()->addMinutes(31));
        $this->post('/users/login', ['email' => $email, 'password' => 'Xk7!qpLm']);

        $this->assertRedirect('/');
    }

    public function testSuccessfulLoginResetsFailureCount(): void
    {
        $email = 'reset-count-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);

        $this->post('/users/login', ['email' => $email, 'password' => 'WrongPass1!']);
        $this->post('/users/login', ['email' => $email, 'password' => 'Xk7!qpLm']);

        $reloaded = $this->Users->get($user->id);
        $this->assertSame(0, $reloaded->failed_login_count);
    }

    public function testLoginPostWithoutCsrfTokenIsRejected(): void
    {
        $this->_csrfToken = false;

        $email = 'nocsrf-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeVerifiedUser($email);

        $this->post('/users/login', ['email' => $email, 'password' => 'Xk7!qpLm']);

        $this->assertResponseCode(403);
    }

    // --- Logout ------------------------------------------------------------

    public function testLogoutRequiresPost(): void
    {
        $this->get('/users/logout');

        $this->assertResponseCode(405);
    }

    // --- Forgot / reset password --------------------------------------------

    public function testForgotPasswordShowsSameScreenForKnownAndUnknownEmail(): void
    {
        $known = 'known-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeVerifiedUser($known);

        $this->post('/users/forgot-password', ['email' => $known]);
        $this->assertResponseOk();
        $knownBody = (string)$this->_response->getBody();

        $this->post('/users/forgot-password', [
            'email' => 'unknown-' . bin2hex(random_bytes(4)) . '@example.com',
        ]);
        $this->assertResponseOk();
        $unknownBody = (string)$this->_response->getBody();

        $this->assertSame($knownBody, $unknownBody);
    }

    public function testResetPasswordWithValidTokenAllowsLogin(): void
    {
        $email = 'resetflow-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email, 'OldPass1!');
        $issued = $this->Tokens->issue($user->id, AccountActionToken::PURPOSE_PASSWORD_RESET);

        $this->post('/users/reset-password', [
            'token' => $issued['token'],
            'password' => 'NewPass2!',
            'password_confirm' => 'NewPass2!',
        ]);
        $this->assertResponseOk();

        $this->post('/users/login', ['email' => $email, 'password' => 'NewPass2!']);
        $this->assertRedirect('/');
    }

    public function testResetPasswordWithInvalidTokenShowsError(): void
    {
        $this->post('/users/reset-password', [
            'token' => 'not-a-real-token',
            'password' => 'NewPass2!',
            'password_confirm' => 'NewPass2!',
        ]);

        $this->assertRedirectContains('/users/forgot-password');
        $this->assertFlashMessage('再設定用のリンクが無効か、有効期限が切れています。パスワード再設定をやり直してください。');
    }

    // --- Security-stamp guard (AppController::beforeFilter) ----------------

    /**
     * A malformed/stale session identity with no usable id must never crash
     * the request; it should simply be treated as invalid and force a
     * logout, regardless of what corrupted it.
     */
    public function testMalformedSessionIdentityDoesNotCrashRequest(): void
    {
        $this->session(['Auth' => ['email' => 'stale@example.com']]);

        $this->get('/users/login');

        // Forced back to the login screen rather than crashing (500).
        $this->assertRedirectContains('/users/login');
    }
}
