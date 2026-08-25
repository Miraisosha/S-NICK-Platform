<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class DashboardControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private $Users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = $this->fetchTable('Users');
        $this->enableCsrfToken();
    }

    protected function tearDown(): void
    {
        unset($this->Users);
        parent::tearDown();
    }

    public function testUnauthenticatedVisitorIsRedirectedToLogin(): void
    {
        $this->get('/dashboard');

        $this->assertRedirectContains('/users/login');
    }

    public function testAuthenticatedUserCanViewDashboard(): void
    {
        $email = 'dashboard-' . bin2hex(random_bytes(4)) . '@example.com';
        $user = $this->Users->saveOrFail($this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => $email,
            'password_hash' => password_hash('Xk7!qpLm', PASSWORD_ARGON2ID),
            'email_verified_at' => DateTime::now(),
        ], ['accessibleFields' => ['*' => true]]));

        // IntegrationTestTrait does not carry session/cookie state between
        // separate get()/post() calls (each gets a fresh Session), so a
        // real login POST followed by a plain GET would not stay "logged
        // in" - seed the session directly instead, as the framework's own
        // docs recommend for this scenario.
        $this->session(['Auth' => ['id' => $user->id, 'email' => $user->email]]);

        $this->get('/dashboard');

        $this->assertResponseOk();
        $this->assertResponseContains('ダッシュボード');
    }
}
