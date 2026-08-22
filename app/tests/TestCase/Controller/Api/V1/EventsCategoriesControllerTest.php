<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class EventsCategoriesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private $Users;
    private $Events;
    private $Categories;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = $this->fetchTable('Users');
        $this->Events = $this->fetchTable('Events');
        $this->Categories = $this->fetchTable('Categories');
    }

    protected function tearDown(): void
    {
        unset($this->Users, $this->Events, $this->Categories);
        parent::tearDown();
    }

    private function makeVerifiedUser(): int
    {
        $user = $this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => 'category-test-' . bin2hex(random_bytes(4)) . '@example.com',
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

    private function makeEvent(int $ownerId): int
    {
        $event = $this->Events->newEntity([
            'name' => 'テスト大会',
            'start_at' => '2026-09-01T09:00:00',
            'end_at' => '2026-09-01T18:00:00',
        ]);
        $event->patch(['owner_user_id' => $ownerId, 'publication_status' => 'published'], ['guard' => false]);
        $this->Events->saveOrFail($event);

        return $event->id;
    }

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
            'name' => 'テストカテゴリ',
            'gender' => 'male',
            'squash_association_registration' => true,
            'entry_fee' => 1000,
            'capacity' => 16,
            'registration_start_at' => '2026-08-01T00:00:00',
            'registration_end_at' => '2026-08-25T23:59:59',
            'waitlist_allowed' => false,
            'match_format' => 'tournament',
            'max_games' => 3,
            'game_end_score' => 11,
            'required_point_diff' => 2,
            'estimated_game_minutes' => 15,
            'min_rest_seconds' => 300,
        ];
    }

    public function testIndexRequiresLogin(): void
    {
        $this->get('/api/v1/events/1/categories');

        $this->assertResponseCode(401);
    }

    public function testAddCreatesCategoryWithComputedFields(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $this->loginAsUser($ownerId);

        $this->jsonRequest($this->samplePayload());
        $this->post("/api/v1/events/{$eventId}/categories");

        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $category = $body['data']['category'];
        $this->assertSame(0, $category['display_order']);
        $this->assertSame('unpublished', $category['publication_status']);
        // 120*2 + 60 + 3*15*60 + (3-1)*120 = 3240
        $this->assertSame(3240, $category['estimated_match_seconds']);
    }

    public function testSecondCategoryGetsNextDisplayOrder(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $this->loginAsUser($ownerId);

        $this->jsonRequest($this->samplePayload(['name' => 'カテゴリA']));
        $this->post("/api/v1/events/{$eventId}/categories");

        $this->jsonRequest($this->samplePayload(['name' => 'カテゴリB']));
        $this->post("/api/v1/events/{$eventId}/categories");

        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(1, $body['data']['category']['display_order']);
    }

    public function testAddRejectsAgeMaxBelowAgeMin(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $this->loginAsUser($ownerId);

        $this->jsonRequest($this->samplePayload(['age_min' => 30, 'age_max' => 20]));
        $this->post("/api/v1/events/{$eventId}/categories");

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('age_max', $body['error']['fields']);
    }

    public function testAddRejectsPointDiffWhenGameEndScoreIsZero(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $this->loginAsUser($ownerId);

        $this->jsonRequest($this->samplePayload(['game_end_score' => 0, 'required_point_diff' => 2]));
        $this->post("/api/v1/events/{$eventId}/categories");

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('required_point_diff', $body['error']['fields']);
    }

    public function testAddRejectsDuplicateNameWithinSameEvent(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $this->loginAsUser($ownerId);

        $this->jsonRequest($this->samplePayload());
        $this->post("/api/v1/events/{$eventId}/categories");
        $this->assertResponseCode(201);

        $this->jsonRequest($this->samplePayload());
        $this->post("/api/v1/events/{$eventId}/categories");

        $this->assertResponseCode(422);
    }

    public function testOtherUserCannotListOrCreateCategories(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);

        $otherUserId = $this->makeVerifiedUser();
        $this->loginAsUser($otherUserId);

        $this->get("/api/v1/events/{$eventId}/categories");
        $this->assertResponseCode(404);

        $this->jsonRequest($this->samplePayload());
        $this->post("/api/v1/events/{$eventId}/categories");
        $this->assertResponseCode(404);
    }

    public function testDeleteSoftDeletesCategory(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $this->loginAsUser($ownerId);

        $this->jsonRequest($this->samplePayload());
        $this->post("/api/v1/events/{$eventId}/categories");
        $categoryId = json_decode((string)$this->_response->getBody(), true)['data']['category']['id'];

        $this->delete("/api/v1/events/{$eventId}/categories/{$categoryId}");

        $this->assertResponseCode(200);
        $reloaded = $this->Categories->get($categoryId);
        $this->assertNotNull($reloaded->deleted_at);

        $this->get("/api/v1/events/{$eventId}/categories");
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertCount(0, $body['data']['categories']);
    }
}
