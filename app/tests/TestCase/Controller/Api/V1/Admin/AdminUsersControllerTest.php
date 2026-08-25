<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Admin;

use App\Model\Entity\Admin;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AdminUsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private $Admins;
    private $Users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Admins = $this->fetchTable('Admins');
        $this->Users = $this->fetchTable('Users');
    }

    protected function tearDown(): void
    {
        unset($this->Admins, $this->Users);
        parent::tearDown();
    }

    private function makeAdmin(string $email, string $password = 'Xk7!qpLm'): Admin
    {
        $hasher = new DefaultPasswordHasher([
            'hashType' => PASSWORD_ARGON2ID,
            'hashOptions' => (array)Configure::read('PasswordHasher.argon2id'),
        ]);

        $admin = $this->Admins->newEntity([
            'admin_code' => 'A' . bin2hex(random_bytes(4)),
            'name' => 'テスト管理者',
            'email' => $email,
            'password_hash' => $hasher->hash($password),
            'role' => Admin::ROLE_ADMIN,
            'status' => 'active',
        ], ['accessibleFields' => ['*' => true]]);

        return $this->Admins->saveOrFail($admin);
    }

    public function testLoginWithValidCredentialsReturnsAdminAndPersistsIdentity(): void
    {
        $email = 'admin-login-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeAdmin($email);

        $this->configRequest([
            'environment' => ['CONTENT_TYPE' => 'application/json'],
            'input' => json_encode(['email' => $email, 'password' => 'Xk7!qpLm']),
        ]);
        $this->post('/api/v1/admin/login');

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame($email, $body['data']['email']);
        $this->assertSame('admin', $body['data']['role']);
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        $email = 'admin-wrongpw-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeAdmin($email);

        $this->configRequest([
            'environment' => ['CONTENT_TYPE' => 'application/json'],
            'input' => json_encode(['email' => $email, 'password' => 'WrongPass1!']),
        ]);
        $this->post('/api/v1/admin/login');

        $this->assertResponseCode(401);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('invalid_credentials', $body['error']['code']);
    }

    public function testMeReturns401WhenNotLoggedIn(): void
    {
        $this->get('/api/v1/admin/me');

        $this->assertResponseCode(401);
    }

    public function testMeReturnsIdentityWhenLoggedIn(): void
    {
        $email = 'admin-me-' . bin2hex(random_bytes(4)) . '@example.com';
        $admin = $this->makeAdmin($email);

        $this->session(['AdminAuth' => [
            'id' => $admin->id,
            'email' => $admin->email,
            'name' => $admin->name,
            'role' => $admin->role,
        ]]);
        $this->get('/api/v1/admin/me');

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame($admin->id, $body['data']['id']);
        $this->assertSame($email, $body['data']['email']);
    }

    public function testLogoutReturnsLoggedOutStatus(): void
    {
        $email = 'admin-logout-' . bin2hex(random_bytes(4)) . '@example.com';
        $admin = $this->makeAdmin($email);

        $this->session(['AdminAuth' => [
            'id' => $admin->id,
            'email' => $admin->email,
            'name' => $admin->name,
            'role' => $admin->role,
        ]]);
        $this->post('/api/v1/admin/logout');

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('logged_out', $body['data']['status']);
    }

    /**
     * Regression test for the session-key-collision class of bug already
     * fixed once in this project (see App\Controller\UsersController's
     * `AuthSecurityStamp` history): a regular user's session must not grant
     * admin access, and vice versa, because they use different session keys
     * (`Auth` vs `AdminAuth`).
     */
    public function testRegularUserSessionDoesNotGrantAdminAccess(): void
    {
        /** @var \App\Model\Entity\User $user */
        $user = $this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => 'plain-user-' . bin2hex(random_bytes(4)) . '@example.com',
            'password_hash' => password_hash('Xk7!qpLm', PASSWORD_ARGON2ID),
            'email_verified_at' => DateTime::now(),
        ], ['accessibleFields' => ['*' => true]]);
        $this->Users->saveOrFail($user);

        $this->session(['Auth' => ['id' => $user->id, 'email' => $user->email]]);
        $this->get('/api/v1/admin/me');

        $this->assertResponseCode(401);
    }

    /**
     * Mirror of the above: an admin session must not grant regular-user
     * access either.
     */
    public function testAdminSessionDoesNotGrantRegularUserAccess(): void
    {
        $email = 'admin-isolation-' . bin2hex(random_bytes(4)) . '@example.com';
        $admin = $this->makeAdmin($email);

        $this->session(['AdminAuth' => [
            'id' => $admin->id,
            'email' => $admin->email,
            'name' => $admin->name,
            'role' => $admin->role,
        ]]);
        $this->get('/api/v1/users/me');

        $this->assertResponseCode(401);
    }
}
