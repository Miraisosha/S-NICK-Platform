<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Admin;

use App\Model\Entity\Admin;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class FacilitiesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private $Admins;
    private $Facilities;
    private $Courts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Admins = $this->fetchTable('Admins');
        $this->Facilities = $this->fetchTable('Facilities');
        $this->Courts = $this->fetchTable('Courts');
    }

    protected function tearDown(): void
    {
        unset($this->Admins, $this->Facilities, $this->Courts);
        parent::tearDown();
    }

    private function loginAsAdmin(): Admin
    {
        $hasher = new DefaultPasswordHasher([
            'hashType' => PASSWORD_ARGON2ID,
            'hashOptions' => (array)Configure::read('PasswordHasher.argon2id'),
        ]);

        $admin = $this->Admins->newEntity([
            'admin_code' => 'A' . bin2hex(random_bytes(4)),
            'name' => 'テスト管理者',
            'email' => 'facadmin-' . bin2hex(random_bytes(4)) . '@example.com',
            'password_hash' => $hasher->hash('Xk7!qpLm'),
            'role' => Admin::ROLE_ADMIN,
            'status' => 'active',
        ], ['accessibleFields' => ['*' => true]]);
        $this->Admins->saveOrFail($admin);

        $this->session(['AdminAuth' => [
            'id' => $admin->id,
            'email' => $admin->email,
            'name' => $admin->name,
            'role' => $admin->role,
        ]]);

        return $admin;
    }

    private function jsonRequest(array $data): void
    {
        $this->configRequest([
            'environment' => ['CONTENT_TYPE' => 'application/json'],
            'input' => json_encode($data),
        ]);
    }

    public function testIndexRequiresAdminAuth(): void
    {
        $this->get('/api/v1/admin/facilities');

        $this->assertResponseCode(401);
    }

    public function testAddCreatesFacilityWithCourts(): void
    {
        $this->loginAsAdmin();

        $this->jsonRequest([
            'name' => 'テスト体育館',
            'prefecture' => '東京都',
            'courts' => [['name' => '1コート'], ['name' => '2コート']],
        ]);
        $this->post('/api/v1/admin/facilities');

        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('テスト体育館', $body['data']['facility']['name']);
        $this->assertCount(2, $body['data']['facility']['courts']);
    }

    public function testEditUpdatesCourtInPlaceWithoutLosingId(): void
    {
        $this->loginAsAdmin();

        $facility = $this->Facilities->saveOrFail($this->Facilities->newEntity(['name' => 'Gym']));
        $court = $this->Courts->saveOrFail($this->Courts->newEntity([
            'facility_id' => $facility->id,
            'name' => 'Court A',
        ]));

        $this->jsonRequest([
            'name' => 'Gym Renamed',
            'courts' => [['id' => $court->id, 'name' => 'Court A Renamed']],
        ]);
        $this->put('/api/v1/admin/facilities/' . $facility->id);

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertCount(1, $body['data']['facility']['courts']);
        $this->assertSame($court->id, $body['data']['facility']['courts'][0]['id']);
        $this->assertSame('Court A Renamed', $body['data']['facility']['courts'][0]['name']);
    }

    public function testEditRemovesCourtsNotInPayloadBySoftDelete(): void
    {
        $this->loginAsAdmin();

        $facility = $this->Facilities->saveOrFail($this->Facilities->newEntity(['name' => 'Gym']));
        $court = $this->Courts->saveOrFail($this->Courts->newEntity([
            'facility_id' => $facility->id,
            'name' => 'Court A',
        ]));

        $this->jsonRequest(['name' => 'Gym', 'courts' => []]);
        $this->put('/api/v1/admin/facilities/' . $facility->id);

        $this->assertResponseCode(200);

        $reloaded = $this->Courts->get($court->id);
        $this->assertNotNull($reloaded->deleted_at, 'Removed court should be soft-deleted, not left active.');

        // The row itself must still exist (soft delete, not a hard DELETE).
        $this->assertSame(1, $this->Courts->find()->where(['id' => $court->id])->count());
    }

    /**
     * Regression test: a court id belonging to a DIFFERENT facility must
     * never be adopted/retargeted by this facility's edit request. Found
     * during manual testing: syncCourts() scopes its "existing courts"
     * lookup to the current facility_id specifically to prevent this.
     */
    public function testEditCannotHijackCourtBelongingToAnotherFacility(): void
    {
        $this->loginAsAdmin();

        $facilityA = $this->Facilities->saveOrFail($this->Facilities->newEntity(['name' => 'Gym A']));
        $courtA = $this->Courts->saveOrFail($this->Courts->newEntity([
            'facility_id' => $facilityA->id,
            'name' => 'Court A',
        ]));
        $facilityB = $this->Facilities->saveOrFail($this->Facilities->newEntity(['name' => 'Gym B']));

        $this->jsonRequest([
            'name' => 'Gym B',
            'courts' => [['id' => $courtA->id, 'name' => 'Hijacked']],
        ]);
        $this->put('/api/v1/admin/facilities/' . $facilityB->id);

        $this->assertResponseCode(200);

        $reloadedCourtA = $this->Courts->get($courtA->id);
        $this->assertSame($facilityA->id, $reloadedCourtA->facility_id);
        $this->assertSame('Court A', $reloadedCourtA->name);

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertNotSame($courtA->id, $body['data']['facility']['courts'][0]['id']);
    }

    public function testDeleteSoftDeletesFacilityAndItsCourts(): void
    {
        $this->loginAsAdmin();

        $facility = $this->Facilities->saveOrFail($this->Facilities->newEntity(['name' => 'Gym']));
        $court = $this->Courts->saveOrFail($this->Courts->newEntity([
            'facility_id' => $facility->id,
            'name' => 'Court A',
        ]));

        $this->delete('/api/v1/admin/facilities/' . $facility->id);

        $this->assertResponseCode(200);
        $this->assertNotNull($this->Facilities->get($facility->id)->deleted_at);
        $this->assertNotNull($this->Courts->get($court->id)->deleted_at);
    }

    public function testIndexExcludesSoftDeletedFacilities(): void
    {
        $this->loginAsAdmin();

        $this->Facilities->saveOrFail($this->Facilities->newEntity([
            'name' => 'Deleted Gym',
            'deleted_at' => DateTime::now(),
        ], ['accessibleFields' => ['*' => true]]));

        $this->get('/api/v1/admin/facilities');

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $names = array_column($body['data']['facilities'], 'name');
        $this->assertNotContains('Deleted Gym', $names);
    }
}
