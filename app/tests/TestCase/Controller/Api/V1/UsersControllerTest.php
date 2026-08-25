<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use App\Model\Entity\AccountActionToken;
use App\Model\Entity\User;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private $Users;
    private $Tokens;
    private string $allowedOrigin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = $this->fetchTable('Users');
        $this->Tokens = $this->fetchTable('AccountActionTokens');
        $this->allowedOrigin = (array)Configure::read('Cors.allowedOrigins') === []
            ? 'http://localhost:5173'
            : (string)((array)Configure::read('Cors.allowedOrigins'))[0];
    }

    protected function tearDown(): void
    {
        unset($this->Users, $this->Tokens);
        parent::tearDown();
    }

    private function withOrigin(): void
    {
        $this->configRequest(['headers' => ['Origin' => $this->allowedOrigin]]);
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

    public function testCorsHeadersPresentForAllowedOrigin(): void
    {
        $this->withOrigin();
        $this->post('/api/v1/users/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertHeader('Access-Control-Allow-Origin', $this->allowedOrigin);
        $this->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function testCorsHeadersAbsentForDisallowedOrigin(): void
    {
        $this->configRequest(['headers' => ['Origin' => 'https://evil.example.com']]);
        $this->post('/api/v1/users/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertFalse($this->_response->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testOptionsPreflightIsAnsweredWithoutRouting(): void
    {
        $this->withOrigin();
        $this->options('/api/v1/users/login');

        $this->assertResponseCode(204);
        $this->assertHeader('Access-Control-Allow-Origin', $this->allowedOrigin);
    }

    public function testRegisterReturns202AndCreatesUnverifiedUser(): void
    {
        $email = 'api-register-' . bin2hex(random_bytes(4)) . '@example.com';

        $this->post('/api/v1/users/register', [
            'email' => $email,
            'password' => 'Xk7!qpLm',
            'password_confirm' => 'Xk7!qpLm',
        ]);

        $this->assertResponseCode(202);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('pending_verification', $body['data']['status']);

        $user = $this->Users->find()->where(['email' => $email])->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
    }

    public function testRegisterWithWeakPasswordReturns422(): void
    {
        $this->post('/api/v1/users/register', [
            'email' => 'api-weak-' . bin2hex(random_bytes(4)) . '@example.com',
            'password' => 'abc',
            'password_confirm' => 'abc',
        ]);

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('weak_password', $body['error']['code']);
    }

    public function testVerifyEmailActivatesAccountAndReturnsUser(): void
    {
        $email = 'api-verify-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->Users->saveOrFail($this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => $email,
            'password_hash' => password_hash('Xk7!qpLm', PASSWORD_ARGON2ID),
        ], ['accessibleFields' => ['*' => true]]));
        $issued = $this->Tokens->issue($user->id, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);

        $this->post('/api/v1/users/verify-email', ['token' => $issued['token']]);

        $this->assertResponseCode(200);
        $reloaded = $this->Users->get($user->id);
        $this->assertNotNull($reloaded->email_verified_at);
    }

    public function testVerifyEmailWithInvalidTokenReturns422(): void
    {
        $this->post('/api/v1/users/verify-email', ['token' => 'not-a-real-token']);

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('invalid_or_expired_token', $body['error']['code']);
    }

    public function testLoginWithValidCredentialsReturnsUserAndPersistsIdentity(): void
    {
        $email = 'api-login-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeVerifiedUser($email);

        $this->post('/api/v1/users/login', ['email' => $email, 'password' => 'Xk7!qpLm']);

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame($email, $body['data']['email']);
    }

    /**
     * Regression test: the real Vue FRONT sends `Content-Type: application/json`
     * bodies (see frontend/src/api/client.js), unlike the other login test above
     * which - like every other test in this class - relies on IntegrationTestTrait's
     * default of encoding array data as `application/x-www-form-urlencoded`. That
     * default meant no test here ever exercised a genuine JSON request body, which
     * is exactly the gap that let a middleware-ordering bug slip through: with
     * `AuthenticationMiddleware` queued before `BodyParserMiddleware` (the
     * unmodified CakePHP skeleton order - see App\Application::middleware()),
     * `FormAuthenticator` read `$request->getData()` before the JSON body had been
     * parsed into it, so every JSON-bodied login request failed identification and
     * returned 401 even with correct credentials. Form-urlencoded logins never
     * noticed because PHP itself populates `$_POST` for that content type,
     * independent of BodyParserMiddleware. Confirmed against the real frontend via
     * manual curl/browser testing before the fix (BodyParserMiddleware now runs
     * before AuthenticationMiddleware) and after.
     */
    public function testLoginWithJsonBodyReturnsUserAndPersistsIdentity(): void
    {
        $email = 'api-login-json-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeVerifiedUser($email);

        $this->configRequest([
            'environment' => ['CONTENT_TYPE' => 'application/json'],
            'input' => json_encode(['email' => $email, 'password' => 'Xk7!qpLm']),
        ]);
        $this->post('/api/v1/users/login');

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame($email, $body['data']['email']);
    }

    public function testLoginWithWrongPasswordReturns401WithGenericMessage(): void
    {
        $email = 'api-wrongpw-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeVerifiedUser($email);

        $this->post('/api/v1/users/login', ['email' => $email, 'password' => 'WrongPass1!']);

        $this->assertResponseCode(401);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('invalid_credentials', $body['error']['code']);
    }

    public function testLoginWithUnknownEmailReturnsSameGenericMessage(): void
    {
        $this->post('/api/v1/users/login', [
            'email' => 'api-unknown-' . bin2hex(random_bytes(4)) . '@example.com',
            'password' => 'WrongPass1!',
        ]);

        $this->assertResponseCode(401);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('invalid_credentials', $body['error']['code']);
    }

    public function testMeReturns401WhenNotLoggedIn(): void
    {
        $this->get('/api/v1/users/me');

        $this->assertResponseCode(401);
    }

    public function testMeReturnsIdentityWhenLoggedIn(): void
    {
        $email = 'api-me-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);

        $this->session(['Auth' => ['id' => $user->id, 'email' => $user->email]]);
        $this->get('/api/v1/users/me');

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame($email, $body['data']['email']);
    }

    public function testLogoutClearsSessionAndMeReturns401Afterward(): void
    {
        $email = 'api-logout-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email);

        $this->session(['Auth' => ['id' => $user->id, 'email' => $user->email]]);
        $this->post('/api/v1/users/logout');

        $this->assertResponseCode(200);
    }

    public function testForgotPasswordAlwaysReturns200(): void
    {
        $this->post('/api/v1/users/forgot-password', [
            'email' => 'api-forgot-unknown-' . bin2hex(random_bytes(4)) . '@example.com',
        ]);

        $this->assertResponseCode(200);
    }

    public function testResetPasswordWithValidTokenAllowsLogin(): void
    {
        $email = 'api-reset-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->makeVerifiedUser($email, 'OldPass1!');
        $issued = $this->Tokens->issue($user->id, AccountActionToken::PURPOSE_PASSWORD_RESET);

        $this->post('/api/v1/users/reset-password', [
            'token' => $issued['token'],
            'password' => 'NewPass2!',
            'password_confirm' => 'NewPass2!',
        ]);
        $this->assertResponseCode(200);

        $this->post('/api/v1/users/login', ['email' => $email, 'password' => 'NewPass2!']);
        $this->assertResponseCode(200);
    }

    public function testResetPasswordWithInvalidTokenReturns422(): void
    {
        $this->post('/api/v1/users/reset-password', [
            'token' => 'not-a-real-token',
            'password' => 'NewPass2!',
            'password_confirm' => 'NewPass2!',
        ]);

        $this->assertResponseCode(422);
    }
}
