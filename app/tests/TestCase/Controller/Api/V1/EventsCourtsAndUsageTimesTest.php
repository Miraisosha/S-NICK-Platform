<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Covers EventsController's courts/usageTimes sub-actions and
 * Api\V1\FacilitiesController (SCR-OPR-261/2402/2404). The admin-only
 * facility/court delete-reference guard
 * (Api\V1\Admin\FacilitiesController::delete()) is covered alongside its
 * other tests in Admin\FacilitiesControllerTest.
 */
class EventsCourtsAndUsageTimesTest extends TestCase
{
    use IntegrationTestTrait;

    private $Users;
    private $Events;
    private $Facilities;
    private $Courts;
    private $EventCourts;
    private $EventCourtUsageTimes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = $this->fetchTable('Users');
        $this->Events = $this->fetchTable('Events');
        $this->Facilities = $this->fetchTable('Facilities');
        $this->Courts = $this->fetchTable('Courts');
        $this->EventCourts = $this->fetchTable('EventCourts');
        $this->EventCourtUsageTimes = $this->fetchTable('EventCourtUsageTimes');
    }

    protected function tearDown(): void
    {
        unset(
            $this->Users,
            $this->Events,
            $this->Facilities,
            $this->Courts,
            $this->EventCourts,
            $this->EventCourtUsageTimes,
        );
        parent::tearDown();
    }

    private function makeVerifiedUser(): int
    {
        $user = $this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => 'm5-test-' . bin2hex(random_bytes(4)) . '@example.com',
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
            'end_at' => '2026-09-02T18:00:00',
        ]);
        $event->patch(['owner_user_id' => $ownerId, 'publication_status' => 'published'], ['guard' => false]);
        $this->Events->saveOrFail($event);

        return $event->id;
    }

    private function makeCourt(): int
    {
        $facility = $this->Facilities->saveOrFail($this->Facilities->newEntity(['name' => 'テスト施設']));
        $court = $this->Courts->saveOrFail($this->Courts->newEntity([
            'facility_id' => $facility->id,
            'name' => 'テストコート',
        ]));

        return $court->id;
    }

    private function jsonRequest(array $data): void
    {
        $this->replaceRequest([
            'environment' => ['CONTENT_TYPE' => 'application/json'],
            'input' => json_encode($data),
        ]);
    }

    public function testFacilitiesIndexRequiresLogin(): void
    {
        $this->get('/api/v1/facilities');

        $this->assertResponseCode(401);
    }

    public function testFacilitiesIndexListsActiveFacilitiesWithCourts(): void
    {
        $this->loginAsUser($this->makeVerifiedUser());
        $this->makeCourt();

        $this->get('/api/v1/facilities');

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotEmpty($body['data']['facilities']);
    }

    public function testUpdateCourtsReplacesEventCourtSelection(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $courtId = $this->makeCourt();
        $this->loginAsUser($ownerId);

        $this->jsonRequest(['court_ids' => [$courtId]]);
        $this->put("/api/v1/events/{$eventId}/courts");

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertCount(1, $body['data']['courts']);
        $this->assertSame($courtId, $body['data']['courts'][0]['court_id']);
    }

    public function testUpdateCourtsRejectsUnknownCourtId(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $this->loginAsUser($ownerId);

        $this->jsonRequest(['court_ids' => [999999]]);
        $this->put("/api/v1/events/{$eventId}/courts");

        $this->assertResponseCode(422);
    }

    public function testUpdateUsageTimesRequiresCourtToBeSelected(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $courtId = $this->makeCourt();
        $this->loginAsUser($ownerId);

        $this->jsonRequest([
            'usage_times' => [
                ['court_id' => $courtId, 'usage_date' => '2026-09-01', 'start_time' => '09:00', 'end_time' => '18:00'],
            ],
        ]);
        $this->put("/api/v1/events/{$eventId}/usage-times");

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('invalid_court', $body['error']['code']);
    }

    public function testUpdateUsageTimesSucceedsForSelectedCourt(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $courtId = $this->makeCourt();
        $this->loginAsUser($ownerId);

        $this->jsonRequest(['court_ids' => [$courtId]]);
        $this->put("/api/v1/events/{$eventId}/courts");
        $this->assertResponseCode(200);

        $this->jsonRequest([
            'usage_times' => [
                ['court_id' => $courtId, 'usage_date' => '2026-09-01', 'start_time' => '09:00', 'end_time' => '18:00'],
                ['court_id' => $courtId, 'usage_date' => '2026-09-02', 'start_time' => '08:00', 'end_time' => '12:00'],
            ],
        ]);
        $this->put("/api/v1/events/{$eventId}/usage-times");

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertCount(2, $body['data']['usageTimes']);
    }

    public function testUpdateUsageTimesRejectsEndTimeBeforeStartTime(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $courtId = $this->makeCourt();
        $this->loginAsUser($ownerId);

        $this->jsonRequest(['court_ids' => [$courtId]]);
        $this->put("/api/v1/events/{$eventId}/courts");

        $this->jsonRequest([
            'usage_times' => [
                ['court_id' => $courtId, 'usage_date' => '2026-09-01', 'start_time' => '18:00', 'end_time' => '09:00'],
            ],
        ]);
        $this->put("/api/v1/events/{$eventId}/usage-times");

        $this->assertResponseCode(422);
    }

    public function testUpdateUsageTimesReplacesWholesale(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $courtId = $this->makeCourt();
        $this->loginAsUser($ownerId);

        $this->jsonRequest(['court_ids' => [$courtId]]);
        $this->put("/api/v1/events/{$eventId}/courts");

        $this->jsonRequest([
            'usage_times' => [
                ['court_id' => $courtId, 'usage_date' => '2026-09-01', 'start_time' => '09:00', 'end_time' => '18:00'],
            ],
        ]);
        $this->put("/api/v1/events/{$eventId}/usage-times");
        $this->assertResponseCode(200);

        // Second call with a different single row must fully replace the first.
        $this->jsonRequest([
            'usage_times' => [
                ['court_id' => $courtId, 'usage_date' => '2026-09-02', 'start_time' => '10:00', 'end_time' => '16:00'],
            ],
        ]);
        $this->put("/api/v1/events/{$eventId}/usage-times");

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertCount(1, $body['data']['usageTimes']);
        $this->assertSame('2026-09-02', $body['data']['usageTimes'][0]['usage_date']);
    }

    public function testOtherUserCannotUpdateCourtsOrUsageTimes(): void
    {
        $ownerId = $this->makeVerifiedUser();
        $eventId = $this->makeEvent($ownerId);
        $courtId = $this->makeCourt();

        $otherUserId = $this->makeVerifiedUser();
        $this->loginAsUser($otherUserId);

        $this->jsonRequest(['court_ids' => [$courtId]]);
        $this->put("/api/v1/events/{$eventId}/courts");
        $this->assertResponseCode(404);

        $this->jsonRequest(['usage_times' => []]);
        $this->put("/api/v1/events/{$eventId}/usage-times");
        $this->assertResponseCode(404);
    }
}
