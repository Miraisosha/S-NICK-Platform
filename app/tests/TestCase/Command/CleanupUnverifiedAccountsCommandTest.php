<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Model\Entity\User;
use Cake\Chronos\Chronos;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class CleanupUnverifiedAccountsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    private $Users;
    private $Attempts;
    private $Tokens;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = $this->fetchTable('Users');
        $this->Attempts = $this->fetchTable('AccountActionAttempts');
        $this->Tokens = $this->fetchTable('AccountActionTokens');
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
        unset($this->Users, $this->Attempts, $this->Tokens);
        parent::tearDown();
    }

    private function makeUser(array $overrides = []): User
    {
        $data = array_merge([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => 'cleanup-' . bin2hex(random_bytes(4)) . '@example.com',
            'password_hash' => 'x',
        ], $overrides);

        return $this->Users->saveOrFail(
            $this->Users->newEntity($data, ['accessibleFields' => ['*' => true]]),
        );
    }

    public function testSoftDeletesUnverifiedAccountsOlderThan24Hours(): void
    {
        Chronos::setTestNow(DateTime::now()->subHours(25));
        $stale = $this->makeUser(['email_verified_at' => null]);

        Chronos::setTestNow();
        $this->exec('cleanup_unverified_accounts');

        $this->assertExitSuccess();
        $reloaded = $this->Users->get($stale->id);
        $this->assertNotNull($reloaded->deleted_at);
    }

    public function testDoesNotTouchRecentUnverifiedAccounts(): void
    {
        $recent = $this->makeUser(['email_verified_at' => null]);

        $this->exec('cleanup_unverified_accounts');

        $this->assertExitSuccess();
        $reloaded = $this->Users->get($recent->id);
        $this->assertNull($reloaded->deleted_at);
    }

    public function testDoesNotTouchVerifiedAccounts(): void
    {
        Chronos::setTestNow(DateTime::now()->subHours(25));
        $verified = $this->makeUser(['email_verified_at' => DateTime::now()]);

        Chronos::setTestNow();
        $this->exec('cleanup_unverified_accounts');

        $reloaded = $this->Users->get($verified->id);
        $this->assertNull($reloaded->deleted_at);
    }

    public function testPurgesAttemptRowsOlderThan30Days(): void
    {
        Chronos::setTestNow(DateTime::now()->subDays(31));
        $old = $this->Attempts->record('old@example.com', null, 'email_verification', 'register', 'sent');

        Chronos::setTestNow();
        $recent = $this->Attempts->record('recent@example.com', null, 'email_verification', 'register', 'sent');

        $this->exec('cleanup_unverified_accounts');

        $this->assertNull($this->Attempts->find()->where(['id' => $old->id])->first());
        $this->assertNotNull($this->Attempts->find()->where(['id' => $recent->id])->first());
    }

    public function testRemovesSettledTokens(): void
    {
        $user = $this->makeUser();
        $expired = $this->Tokens->issue($user->id, 'email_verification');
        Chronos::setTestNow(DateTime::now()->addMinutes(61));

        $this->exec('cleanup_unverified_accounts');

        $this->assertNull($this->Tokens->find()->where(['id' => $expired['entity']->id])->first());
    }
}
