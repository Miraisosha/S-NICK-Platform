<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class EventsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private $Users;
    private $Events;
    private $EventStaff;
    private $EventStaffRoles;
    private $Roles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = $this->fetchTable('Users');
        $this->Events = $this->fetchTable('Events');
        $this->EventStaff = $this->fetchTable('EventStaff');
        $this->EventStaffRoles = $this->fetchTable('EventStaffRoles');
        $this->Roles = $this->fetchTable('Roles');
    }

    protected function tearDown(): void
    {
        unset($this->Users, $this->Events, $this->EventStaff, $this->EventStaffRoles, $this->Roles);
        parent::tearDown();
    }

    /**
     * The migration that seeds fixed roles only runs once (tracked as
     * "applied"); this project's shared dev/test database means its rows
     * can be wiped by other test runs without the seed re-firing (see the
     * implementation notes for M3). Tests must not depend on that external
     * state, so ensure the role exists here regardless.
     */
    private function ensureRole(string $code, string $name): int
    {
        $role = $this->Roles->find()->where(['code' => $code])->first();
        if ($role !== null) {
            return $role->id;
        }

        $role = $this->Roles->newEntity(
            ['code' => $code, 'name' => $name],
            ['accessibleFields' => ['*' => true]],
        );
        $this->Roles->saveOrFail($role);

        return $role->id;
    }

    private function makeVerifiedUser(): int
    {
        $user = $this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => 'event-test-' . bin2hex(random_bytes(4)) . '@example.com',
            'password_hash' => password_hash('Xk7!qpLm', PASSWORD_ARGON2ID),
            'email_verified_at' => DateTime::now(),
        ], ['accessibleFields' => ['*' => true]]);
        $this->Users->saveOrFail($user);

        return $user->id;
    }

    private function loginAsUser(int $userId): void
    {
        $user = $this->Users->get($userId);
        $this->session(['Auth' => ['id' => $user->id, 'email' => $user->email]]);
    }

    /**
     * Uses replaceRequest(), not configRequest(): configRequest() merges
     * into any existing $this->_request via array_merge_recursive(), which
     * for a second call in the same test turns the string `input` key into
     * `[newJson, oldJson]` - crashing the next request when the harness
     * tries to write that array as the request body. Found this while
     * writing testEventManagerStaffCanEditEvent (two requests per test).
     */
    private function jsonRequest(array $data): void
    {
        $this->replaceRequest([
            'environment' => ['CONTENT_TYPE' => 'application/json'],
            'input' => json_encode($data),
        ]);
    }

    private function samplePayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'テスト大会',
            'start_at' => '2026-09-01T09:00:00',
            'end_at' => '2026-09-01T18:00:00',
        ];
    }

    public function testIndexRequiresLogin(): void
    {
        $this->get('/api/v1/events');

        $this->assertResponseCode(401);
    }

    public function testAddCreatesEventAndAssignsOwnerRole(): void
    {
        $this->ensureRole('event_owner', 'イベント所有者');
        $userId = $this->makeVerifiedUser();
        $this->loginAsUser($userId);

        $this->jsonRequest($this->samplePayload());
        $this->post('/api/v1/events');

        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame($userId, $body['data']['event']['owner_user_id']);
        $this->assertSame('published', $body['data']['event']['publication_status']);

        $eventId = $body['data']['event']['id'];
        $staffRoleCount = $this->EventStaffRoles->find()
            ->matching('EventStaff', function ($q) use ($eventId, $userId) {
                return $q->where(['EventStaff.event_id' => $eventId, 'EventStaff.user_id' => $userId]);
            })
            ->matching('Roles', function ($q) {
                return $q->where(['Roles.code' => 'event_owner']);
            })
            ->count();
        $this->assertSame(1, $staffRoleCount, 'Creator should be registered as event_owner staff.');
    }

    public function testAddRejectsEndBeforeStart(): void
    {
        $this->ensureRole('event_owner', 'イベント所有者');
        $this->loginAsUser($this->makeVerifiedUser());

        $this->jsonRequest($this->samplePayload([
            'start_at' => '2026-09-01T18:00:00',
            'end_at' => '2026-09-01T09:00:00',
        ]));
        $this->post('/api/v1/events');

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('end_at', $body['error']['fields']);
    }

    public function testOtherUserCannotViewOrEditEvent(): void
    {
        $this->ensureRole('event_owner', 'イベント所有者');
        $ownerId = $this->makeVerifiedUser();
        $this->loginAsUser($ownerId);
        $this->jsonRequest($this->samplePayload());
        $this->post('/api/v1/events');
        $eventId = json_decode((string)$this->_response->getBody(), true)['data']['event']['id'];

        $otherUserId = $this->makeVerifiedUser();
        $this->loginAsUser($otherUserId);

        $this->get('/api/v1/events/' . $eventId);
        $this->assertResponseCode(404);

        $this->jsonRequest(['name' => 'Hijacked']);
        $this->put('/api/v1/events/' . $eventId);
        $this->assertResponseCode(404);
    }

    public function testEventManagerStaffCanEditEvent(): void
    {
        $this->ensureRole('event_owner', 'イベント所有者');
        $managerRoleId = $this->ensureRole('event_manager', '大会運営管理者');

        $ownerId = $this->makeVerifiedUser();
        $this->loginAsUser($ownerId);
        $this->jsonRequest($this->samplePayload());
        $this->post('/api/v1/events');
        $eventId = json_decode((string)$this->_response->getBody(), true)['data']['event']['id'];

        $managerId = $this->makeVerifiedUser();
        $staff = $this->EventStaff->newEntity([
            'event_id' => $eventId,
            'user_id' => $managerId,
            'membership_status' => 'active',
            'joined_at' => DateTime::now(),
        ], ['accessibleFields' => ['*' => true]]);
        $this->EventStaff->saveOrFail($staff);
        $staffRole = $this->EventStaffRoles->newEntity([
            'event_staff_id' => $staff->id,
            'role_id' => $managerRoleId,
        ], ['accessibleFields' => ['*' => true]]);
        $this->EventStaffRoles->saveOrFail($staffRole);

        $this->loginAsUser($managerId);
        $this->jsonRequest(['name' => 'Managed Rename']);
        $this->put('/api/v1/events/' . $eventId);

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('Managed Rename', $body['data']['event']['name']);
    }
}
