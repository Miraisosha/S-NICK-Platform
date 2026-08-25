<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class UsersTableTest extends TestCase
{
    private UsersTable $Users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = $this->fetchTable('Users');
    }

    protected function tearDown(): void
    {
        unset($this->Users);
        parent::tearDown();
    }

    public function testNormalizeEmailTrimsAndLowercases(): void
    {
        $this->assertSame('user@example.com', UsersTable::normalizeEmail('  User@Example.COM  '));
    }

    private function makeUser(array $overrides = []): User
    {
        $data = array_merge([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => 'user' . bin2hex(random_bytes(4)) . '@example.com',
            'password_hash' => password_hash('irrelevant', PASSWORD_ARGON2ID),
            'status' => 'active',
            'email_verified_at' => null,
            'deleted_at' => null,
            'locked_until' => null,
        ], $overrides);

        $user = $this->Users->newEntity($data, ['accessibleFields' => ['*' => true]]);

        return $this->Users->saveOrFail($user);
    }

    public function testEmailMustBeUnique(): void
    {
        $email = 'dup' . bin2hex(random_bytes(4)) . '@example.com';
        $this->makeUser(['email' => $email]);

        $second = $this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => $email,
            'password_hash' => 'x',
        ], ['accessibleFields' => ['*' => true]]);

        $this->assertFalse($this->Users->save($second));
        $this->assertArrayHasKey('email', $second->getErrors());
    }

    public function testFindActiveExcludesSoftDeleted(): void
    {
        $deleted = $this->makeUser(['deleted_at' => DateTime::now()]);

        $found = $this->Users->find('active')->where(['id' => $deleted->id])->first();

        $this->assertNull($found);
    }

    public function testFindActiveExcludesSuspended(): void
    {
        $suspended = $this->makeUser(['status' => 'suspended']);

        $found = $this->Users->find('active')->where(['id' => $suspended->id])->first();

        $this->assertNull($found);
    }

    public function testFindAuthableRequiresVerifiedEmail(): void
    {
        $unverified = $this->makeUser(['email_verified_at' => null]);
        $verified = $this->makeUser(['email_verified_at' => DateTime::now()]);

        $ids = $this->Users->find('authable')
            ->where(['id IN' => [$unverified->id, $verified->id]])
            ->all()
            ->extract('id')
            ->toList();

        $this->assertNotContains($unverified->id, $ids);
        $this->assertContains($verified->id, $ids);
    }

    public function testFindLoginableExcludesCurrentlyLockedAccounts(): void
    {
        $locked = $this->makeUser([
            'email_verified_at' => DateTime::now(),
            'locked_until' => DateTime::now()->addMinutes(30),
        ]);
        $expiredLock = $this->makeUser([
            'email_verified_at' => DateTime::now(),
            'locked_until' => DateTime::now()->subMinutes(1),
        ]);

        $ids = $this->Users->find('loginable')
            ->where(['id IN' => [$locked->id, $expiredLock->id]])
            ->all()
            ->extract('id')
            ->toList();

        $this->assertNotContains($locked->id, $ids);
        $this->assertContains($expiredLock->id, $ids);
    }

    public function testPasswordHashIsHiddenFromArrayConversion(): void
    {
        $user = $this->makeUser();

        $this->assertArrayNotHasKey('password_hash', $user->toArray());
    }
}
