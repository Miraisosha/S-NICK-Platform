<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AccountActionAttemptsTable;
use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class AccountActionAttemptsTableTest extends TestCase
{
    private AccountActionAttemptsTable $Attempts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Attempts = $this->fetchTable('AccountActionAttempts');
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
        unset($this->Attempts);
        parent::tearDown();
    }

    public function testLastSentAtReturnsMostRecentSentTimestamp(): void
    {
        $email = 'attempts-' . bin2hex(random_bytes(4)) . '@example.com';

        Chronos::setTestNow(DateTime::now()->subMinutes(10));
        $this->Attempts->record($email, null, 'email_verification', 'register', 'sent');

        Chronos::setTestNow(DateTime::now()->addMinutes(5));
        $second = $this->Attempts->record($email, null, 'email_verification', 'resend', 'sent');

        $lastSentAt = $this->Attempts->lastSentAt($email, 'email_verification');

        $this->assertNotNull($lastSentAt);
        $this->assertSame(
            $second->created->format('Y-m-d H:i:s'),
            $lastSentAt->format('Y-m-d H:i:s'),
        );
    }

    public function testLastSentAtIgnoresNonSentOutcomes(): void
    {
        $email = 'attempts-' . bin2hex(random_bytes(4)) . '@example.com';
        $this->Attempts->record($email, null, 'email_verification', 'resend', 'throttled_cooldown');

        $this->assertNull($this->Attempts->lastSentAt($email, 'email_verification'));
    }

    public function testCountSentSinceCountsOnlyWithinWindow(): void
    {
        $email = 'attempts-' . bin2hex(random_bytes(4)) . '@example.com';

        Chronos::setTestNow(DateTime::now()->subHours(30));
        $this->Attempts->record($email, null, 'email_verification', 'register', 'sent');

        Chronos::setTestNow(DateTime::now()->addHours(29));
        $this->Attempts->record($email, null, 'email_verification', 'resend', 'sent');
        $this->Attempts->record($email, null, 'email_verification', 'resend', 'sent');

        $since = DateTime::now()->subDays(1);
        $this->assertSame(2, $this->Attempts->countSentSince($email, 'email_verification', $since));
    }

    public function testDeleteOlderThanRemovesOnlyStaleRows(): void
    {
        $email = 'attempts-' . bin2hex(random_bytes(4)) . '@example.com';

        Chronos::setTestNow(DateTime::now()->subDays(40));
        $old = $this->Attempts->record($email, null, 'email_verification', 'register', 'sent');

        Chronos::setTestNow(DateTime::now()->addDays(39));
        $recent = $this->Attempts->record($email, null, 'email_verification', 'resend', 'sent');

        $threshold = DateTime::now()->subDays(30);
        $this->Attempts->deleteOlderThan($threshold);

        $this->assertNull($this->Attempts->find()->where(['id' => $old->id])->first());
        $this->assertNotNull($this->Attempts->find()->where(['id' => $recent->id])->first());
    }
}
