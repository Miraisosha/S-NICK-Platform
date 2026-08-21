<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Auth;

use App\Mailer\AuthMailer;
use App\Model\Entity\AccountActionToken;
use App\Model\Entity\User;
use App\Service\Auth\Exception\InvalidOrExpiredTokenException;
use App\Service\Auth\Exception\WeakPasswordException;
use App\Service\Auth\PasswordPolicy;
use App\Service\Auth\PasswordResetService;
use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class PasswordResetServiceTest extends TestCase
{
    private PasswordResetService $service;
    private $Users;
    private $Tokens;
    private $Attempts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadRoutes();

        $this->Users = $this->fetchTable('Users');
        $this->Tokens = $this->fetchTable('AccountActionTokens');
        $this->Attempts = $this->fetchTable('AccountActionAttempts');

        $this->service = new PasswordResetService(
            $this->Users,
            $this->Tokens,
            $this->Attempts,
            new PasswordPolicy(),
            new AuthMailer(),
        );
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
        unset($this->service, $this->Users, $this->Tokens, $this->Attempts);
        parent::tearDown();
    }

    private function makeVerifiedUser(string $email): User
    {
        $user = $this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => $email,
            'password_hash' => password_hash('OldPass1!', PASSWORD_ARGON2ID),
            'email_verified_at' => DateTime::now(),
        ], ['accessibleFields' => ['*' => true]]);

        return $this->Users->saveOrFail($user);
    }

    public function testRequestResetForUnknownEmailSendsNothingButLogsAttempt(): void
    {
        $email = 'unknown-' . bin2hex(random_bytes(4)) . '@example.com';

        $this->service->requestReset($email, '127.0.0.1', 'PHPUnit');

        $attempt = $this->Attempts->find()
            ->where(['normalized_email' => $email, 'outcome' => 'not_found'])
            ->first();
        $this->assertNotNull($attempt);
    }

    public function testRequestResetForKnownUserIssuesToken(): void
    {
        $email = 'known-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);

        $this->service->requestReset($email, null, null);

        $token = $this->Tokens->find()
            ->where(['user_id' => $user->id, 'purpose' => AccountActionToken::PURPOSE_PASSWORD_RESET])
            ->first();
        $this->assertNotNull($token);
    }

    public function testRequestResetForUnverifiedUserDoesNothing(): void
    {
        $email = 'unverified-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => $email,
            'password_hash' => password_hash('OldPass1!', PASSWORD_ARGON2ID),
            'email_verified_at' => null,
        ], ['accessibleFields' => ['*' => true]]);
        $user = $this->Users->saveOrFail($user);

        $this->service->requestReset($email, null, null);

        $token = $this->Tokens->find()->where(['user_id' => $user->id])->first();
        $this->assertNull($token);
    }

    public function testResetPasswordUpdatesHashAndClearsLockout(): void
    {
        $email = 'reset-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);
        $user->failed_login_count = 4;
        $user->locked_until = DateTime::now()->addMinutes(10);
        $this->Users->save($user, ['checkRules' => false, 'validate' => false]);

        $issued = $this->Tokens->issue($user->id, AccountActionToken::PURPOSE_PASSWORD_RESET);

        $updated = $this->service->resetPassword($issued['token'], 'Xk7!qpLm', 'Xk7!qpLm');

        $this->assertNotSame($user->password_hash, $updated->password_hash);
        $this->assertTrue(password_verify('Xk7!qpLm', $updated->password_hash));
        $this->assertSame(0, $updated->failed_login_count);
        $this->assertNull($updated->locked_until);
    }

    public function testResetPasswordBumpsSecurityStamp(): void
    {
        $email = 'stamp-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);
        $this->assertNull($user->sessions_invalidated_at);

        $issued = $this->Tokens->issue($user->id, AccountActionToken::PURPOSE_PASSWORD_RESET);
        $updated = $this->service->resetPassword($issued['token'], 'Xk7!qpLm', 'Xk7!qpLm');

        $this->assertNotNull($updated->sessions_invalidated_at);
    }

    public function testResetPasswordRejectsMismatchedConfirmation(): void
    {
        $email = 'mismatch-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);
        $issued = $this->Tokens->issue($user->id, AccountActionToken::PURPOSE_PASSWORD_RESET);

        $this->expectException(WeakPasswordException::class);
        $this->service->resetPassword($issued['token'], 'Xk7!qpLm', 'Different1!');
    }

    public function testResetPasswordRejectsInvalidToken(): void
    {
        $this->expectException(InvalidOrExpiredTokenException::class);
        $this->service->resetPassword('does-not-exist', 'Xk7!qpLm', 'Xk7!qpLm');
    }

    public function testResetPasswordTokenIsSingleUse(): void
    {
        $email = 'single-use-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);
        $issued = $this->Tokens->issue($user->id, AccountActionToken::PURPOSE_PASSWORD_RESET);

        $this->service->resetPassword($issued['token'], 'Xk7!qpLm', 'Xk7!qpLm');

        $this->expectException(InvalidOrExpiredTokenException::class);
        $this->service->resetPassword($issued['token'], 'Another1!', 'Another1!');
    }

    public function testRequestResetResendInvalidatesPriorToken(): void
    {
        $email = 'resend-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);

        $this->service->requestReset($email, null, null);
        $firstToken = $this->Tokens->find()
            ->where(['user_id' => $user->id, 'invalidated_at IS' => null])
            ->firstOrFail();

        Chronos::setTestNow(DateTime::now()->addSeconds(61));
        $this->service->requestReset($email, null, null);

        $reloaded = $this->Tokens->get($firstToken->id);
        $this->assertNotNull($reloaded->invalidated_at);
    }
}
