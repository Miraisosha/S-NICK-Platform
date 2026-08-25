<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Auth;

use App\Mailer\AuthMailer;
use App\Model\Entity\AccountActionToken;
use App\Service\Auth\Exception\InvalidOrExpiredTokenException;
use App\Service\Auth\Exception\ResendThrottledException;
use App\Service\Auth\Exception\WeakPasswordException;
use App\Service\Auth\PasswordPolicy;
use App\Service\Auth\RegistrationService;
use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class RegistrationServiceTest extends TestCase
{
    private RegistrationService $service;
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

        $this->service = new RegistrationService(
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

    private function registerData(string $email, string $password = 'Xk7!qpLm'): array
    {
        return [
            'email' => $email,
            'password' => $password,
            'password_confirm' => $password,
        ];
    }

    public function testRegisterCreatesUnverifiedUserAndSendsToken(): void
    {
        $email = 'new-' . bin2hex(random_bytes(4)) . '@example.com';

        $this->service->register($this->registerData($email), '127.0.0.1', 'PHPUnit');

        $user = $this->Users->find()->where(['email' => $email])->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertNotEmpty($user->password_hash);
        $this->assertNotSame('Xk7!qpLm', $user->password_hash);

        $token = $this->Tokens->find()
            ->where(['user_id' => $user->id, 'purpose' => AccountActionToken::PURPOSE_EMAIL_VERIFICATION])
            ->first();
        $this->assertNotNull($token);

        $sentAttempt = $this->Attempts->find()
            ->where(['normalized_email' => $email, 'outcome' => 'sent'])
            ->first();
        $this->assertNotNull($sentAttempt);
    }

    public function testRegisterNormalizesEmail(): void
    {
        $email = 'Mixed-' . bin2hex(random_bytes(4)) . '@Example.COM';

        $this->service->register($this->registerData($email), null, null);

        $user = $this->Users->find()
            ->where(['email' => mb_strtolower(trim($email))])
            ->first();
        $this->assertNotNull($user);
    }

    public function testRegisterRejectsWeakPassword(): void
    {
        $this->expectException(WeakPasswordException::class);
        $this->service->register($this->registerData('weak@example.com', 'abc'), null, null);
    }

    public function testRegisterRejectsMismatchedConfirmation(): void
    {
        $email = 'mismatch-' . bin2hex(random_bytes(4)) . '@example.com';

        try {
            $this->service->register([
                'email' => $email,
                'password' => 'Xk7!qpLm',
                'password_confirm' => 'Different1!',
            ], null, null);
            $this->fail('Expected WeakPasswordException was not thrown.');
        } catch (WeakPasswordException $e) {
            $this->assertSame(WeakPasswordException::CONFIRMATION_MISMATCH, $e->getReasonCode());
        }
    }

    public function testDuplicatePendingRegistrationDoesNotCreateSecondRow(): void
    {
        $email = 'pending-' . bin2hex(random_bytes(4)) . '@example.com';

        $this->service->register($this->registerData($email), null, null);
        $this->service->register($this->registerData($email), null, null);

        $count = $this->Users->find()->where(['email' => $email])->count();
        $this->assertSame(1, $count);
    }

    public function testDuplicatePendingRegistrationInvalidatesPriorToken(): void
    {
        $email = 'pending-' . bin2hex(random_bytes(4)) . '@example.com';

        $this->service->register($this->registerData($email), null, null);
        $user = $this->Users->find()->where(['email' => $email])->firstOrFail();
        $firstToken = $this->Tokens->find()
            ->where(['user_id' => $user->id, 'invalidated_at IS' => null])
            ->firstOrFail();

        // Move past the cooldown so the second registration attempt actually resends.
        Chronos::setTestNow(DateTime::now()->addSeconds(61));
        $this->service->register($this->registerData($email), null, null);

        $reloaded = $this->Tokens->get($firstToken->id);
        $this->assertNotNull($reloaded->invalidated_at);
    }

    public function testRegisterAgainstAlreadyVerifiedAccountSendsNoNewToken(): void
    {
        $email = 'verified-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->service->register($this->registerData($email), null, null);

        $user = $this->Users->find()->where(['email' => $email])->firstOrFail();
        $user->set('email_verified_at', DateTime::now(), ['guard' => false]);
        $this->Users->saveOrFail($user);

        $tokenCountBefore = $this->Tokens->find()->where(['user_id' => $user->id])->count();

        $this->service->register($this->registerData($email), null, null);

        $tokenCountAfter = $this->Tokens->find()->where(['user_id' => $user->id])->count();
        $this->assertSame($tokenCountBefore, $tokenCountAfter);

        $attempt = $this->Attempts->find()
            ->where(['normalized_email' => $email, 'outcome' => 'already_verified'])
            ->first();
        $this->assertNotNull($attempt);
    }

    public function testRegisterAgainstSoftDeletedAccountReactivatesIt(): void
    {
        $email = 'reactivate-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->service->register($this->registerData($email, 'OldPass1!'), null, null);

        $user = $this->Users->find()->where(['email' => $email])->firstOrFail();
        $user->set('deleted_at', DateTime::now(), ['guard' => false]);
        $this->Users->saveOrFail($user);

        // Past the 60-second resend cooldown from the initial registration.
        Chronos::setTestNow(DateTime::now()->addSeconds(61));
        $this->service->register($this->registerData($email, 'NewPass2!'), null, null);

        $reloaded = $this->Users->find()->where(['email' => $email])->firstOrFail();
        $this->assertNull($reloaded->deleted_at);
        $this->assertNull($reloaded->email_verified_at);
    }

    public function testResendVerificationThrottlesWithinCooldown(): void
    {
        $email = 'cooldown-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->service->register($this->registerData($email), null, null);

        $this->expectException(ResendThrottledException::class);
        $this->service->resendVerification($email, null, null);
    }

    public function testResendVerificationThrottlesAfterDailyLimit(): void
    {
        $email = 'daily-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->service->register($this->registerData($email), null, null);

        // First registration already used up 1 of the 5 allowed sends.
        for ($i = 0; $i < 3; $i++) {
            Chronos::setTestNow(DateTime::now()->addSeconds(61));
            $this->service->resendVerification($email, null, null);
        }

        Chronos::setTestNow(DateTime::now()->addSeconds(61));
        // 5th send (register + 4 resends) succeeds.
        $this->service->resendVerification($email, null, null);

        Chronos::setTestNow(DateTime::now()->addSeconds(61));
        $this->expectException(ResendThrottledException::class);
        $this->service->resendVerification($email, null, null);
    }

    public function testVerifyEmailMarksUserVerifiedAndTokenUsed(): void
    {
        $email = 'verify-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->service->register($this->registerData($email), null, null);

        $user = $this->Users->find()->where(['email' => $email])->firstOrFail();

        // The service never persists the raw token, so re-issue one directly
        // against the same row to simulate "the email link the user clicked".
        $this->Tokens->invalidateActive($user->id, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);
        $reissued = $this->Tokens->issue($user->id, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);

        $verified = $this->service->verifyEmail($reissued['token']);

        $this->assertNotNull($verified->email_verified_at);

        $usedToken = $this->Tokens->get($reissued['entity']->id);
        $this->assertNotNull($usedToken->used_at);
    }

    public function testVerifyEmailRejectsUnknownToken(): void
    {
        $this->expectException(InvalidOrExpiredTokenException::class);
        $this->service->verifyEmail('does-not-exist');
    }

    public function testVerifyEmailRejectsAlreadyUsedToken(): void
    {
        $email = 'reuse-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->service->register($this->registerData($email), null, null);
        $user = $this->Users->find()->where(['email' => $email])->firstOrFail();

        $this->Tokens->invalidateActive($user->id, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);
        $issued = $this->Tokens->issue($user->id, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);

        $this->service->verifyEmail($issued['token']);

        $this->expectException(InvalidOrExpiredTokenException::class);
        $this->service->verifyEmail($issued['token']);
    }
}
