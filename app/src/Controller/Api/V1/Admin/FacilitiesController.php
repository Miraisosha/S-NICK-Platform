<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Model\Entity\Court;
use App\Model\Entity\Facility;
use App\Model\Table\CourtsTable;
use App\Model\Table\EventCourtsTable;
use App\Model\Table\FacilitiesTable;
use Authentication\IdentityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Exception\PersistenceFailedException;

/**
 * JSON API for SCR-ADM-522 施設・コート管理. Admin-only (see
 * Api\V1\Admin\AppController and Application::getAdminAuthenticationService()).
 * A facility's courts are edited in the same request as the facility
 * itself, since the screen edits both together - `syncCourts()` below
 * handles this explicitly rather than via CakePHP's automatic nested-save,
 * see that method's docblock for why.
 */
class FacilitiesController extends AppController
{
    /**
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        $this->request->allowMethod(['get']);

        if ($this->requireAdmin() === null) {
            return $this->unauthenticated();
        }

        $facilities = $this->facilitiesTable()->find('active')
            ->contain(['Courts' => function ($query) {
                return $query->find('active');
            }])
            ->orderBy(['name' => 'ASC'])
            ->all();

        return $this->json(['facilities' => $facilities]);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);

        if ($this->requireAdmin() === null) {
            return $this->unauthenticated();
        }

        $data = (array)$this->request->getData();
        $courtsData = (array)($data['courts'] ?? []);
        unset($data['courts']);

        $facility = $this->facilitiesTable()->newEntity($data);

        if ($facility->hasErrors()) {
            return $this->validationError($facility->getErrors());
        }

        try {
            $this->facilitiesTable()->getConnection()->transactional(
                function () use ($facility, $courtsData): void {
                    $this->facilitiesTable()->saveOrFail($facility);
                    $this->syncCourts($facility, $courtsData);
                },
            );
        } catch (PersistenceFailedException $e) {
            return $this->validationError($e->getEntity()->getErrors());
        }

        return $this->json(['facility' => $this->reloadFacility((int)$facility->id)], 201);
    }

    /**
     * @param string|null $id Facility id.
     * @return \Cake\Http\Response
     */
    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'patch', 'put']);

        if ($this->requireAdmin() === null) {
            return $this->unauthenticated();
        }

        try {
            $facility = $this->facilitiesTable()->find('active')
                ->where(['id' => $id])
                ->firstOrFail();
        } catch (RecordNotFoundException) {
            return $this->jsonError('not_found', __('施設が見つかりません。'), 404);
        }

        $data = (array)$this->request->getData();
        $courtsData = (array)($data['courts'] ?? []);
        unset($data['courts']);

        $facility = $this->facilitiesTable()->patchEntity($facility, $data);

        if ($facility->hasErrors()) {
            return $this->validationError($facility->getErrors());
        }

        try {
            $this->facilitiesTable()->getConnection()->transactional(
                function () use ($facility, $courtsData): void {
                    $this->facilitiesTable()->saveOrFail($facility);
                    $this->syncCourts($facility, $courtsData);
                },
            );
        } catch (PersistenceFailedException $e) {
            return $this->validationError($e->getEntity()->getErrors());
        }

        return $this->json(['facility' => $this->reloadFacility((int)$facility->id)]);
    }

    /**
     * Soft-deletes the facility and all of its courts together: a court
     * without an active facility has no business appearing in event
     * court-selection lists.
     *
     * @param string|null $id Facility id.
     * @return \Cake\Http\Response
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->requireAdmin() === null) {
            return $this->unauthenticated();
        }

        try {
            $facility = $this->facilitiesTable()->find('active')
                ->where(['id' => $id])
                ->firstOrFail();
        } catch (RecordNotFoundException) {
            return $this->jsonError('not_found', __('施設が見つかりません。'), 404);
        }

        // SCR-ADM-522 "イベントまたは試合で参照中の施設・コートは、影響を確認せず
        // 削除できない": checked here rather than as a CourtsTable buildRules()
        // guard, because the actual deletion below is a bulk updateAll() (all
        // of a facility's courts at once), which - unlike an entity save() -
        // never runs buildRules() at all.
        $referencedCourtCount = $this->eventCourtsTable()->find()
            ->matching('Courts', function ($q) use ($facility) {
                return $q->where(['Courts.facility_id' => $facility->id]);
            })
            ->count();
        if ($referencedCourtCount > 0) {
            return $this->jsonError(
                'referenced',
                __('この施設のコートはイベントで使用されているため削除できません。'),
                422,
            );
        }

        $now = DateTime::now();
        $this->facilitiesTable()->getConnection()->transactional(function () use ($facility, $now): void {
            $this->courtsTable()->updateAll(
                ['deleted_at' => $now],
                ['facility_id' => $facility->id, 'deleted_at IS' => null],
            );
            $facility->set('deleted_at', $now, ['guard' => false]);
            $this->facilitiesTable()->saveOrFail($facility);
        });

        return $this->json(['status' => 'deleted']);
    }

    /**
     * Adds/updates/soft-deletes this facility's courts to match the
     * incoming list. Deliberately not delegated to CakePHP's
     * `saveStrategy => 'replace'`: that only matches existing rows by id
     * when the parent entity was loaded with `contain(['Courts'])` in the
     * first place (this controller doesn't, to keep the common-case load
     * cheap), and even when it does match, it hard-DELETEs anything not in
     * the incoming list - wrong for a soft-deletable table that M5 will
     * also FK-restrict via `event_courts`. Doing the sync explicitly here
     * means every removal always goes through the same soft-delete path.
     *
     * @param \App\Model\Entity\Facility $facility The already-saved facility.
     * @param array<int, array<string, mixed>> $courtsData Raw `courts` array from the request body.
     * @return void
     */
    private function syncCourts(Facility $facility, array $courtsData): void
    {
        /** @var array<int, \App\Model\Entity\Court> $existing */
        $existing = $this->courtsTable()->find('active')
            ->where(['facility_id' => $facility->id])
            ->all()
            ->indexBy('id')
            ->toArray();

        $keptIds = [];

        foreach ($courtsData as $courtData) {
            $courtId = isset($courtData['id']) ? (int)$courtData['id'] : null;
            $existingCourt = $courtId !== null ? ($existing[$courtId] ?? null) : null;

            if ($existingCourt instanceof Court) {
                $court = $this->courtsTable()->patchEntity($existingCourt, $courtData);
            } else {
                $court = $this->courtsTable()->newEntity($courtData);
                $court->set('facility_id', $facility->id, ['guard' => false]);
            }

            $this->courtsTable()->saveOrFail($court);
            $keptIds[] = $court->id;
        }

        $removedIds = array_diff(array_keys($existing), $keptIds);
        if ($removedIds !== []) {
            $this->courtsTable()->updateAll(
                ['deleted_at' => DateTime::now()],
                ['id IN' => $removedIds],
            );
        }
    }

    /**
     * @param array<string, mixed> $errors Validation errors from the entity.
     * @return \Cake\Http\Response
     */
    private function validationError(array $errors): Response
    {
        return $this->jsonError('validation_failed', __('入力内容を確認してください。'), 422, ['fields' => $errors]);
    }

    /**
     * @return \Cake\Http\Response
     */
    private function unauthenticated(): Response
    {
        return $this->jsonError('unauthenticated', __('ログインしていません。'), 401);
    }

    /**
     * @return \Authentication\IdentityInterface|null
     */
    private function requireAdmin(): ?IdentityInterface
    {
        return $this->Authentication->getIdentity();
    }

    /**
     * @param int $id Facility id.
     * @return \App\Model\Entity\Facility
     */
    private function reloadFacility(int $id): Facility
    {
        /** @var \App\Model\Entity\Facility */
        return $this->facilitiesTable()->find('active')
            ->where(['id' => $id])
            ->contain(['Courts' => function ($query) {
                return $query->find('active');
            }])
            ->firstOrFail();
    }

    /**
     * @return \App\Model\Table\FacilitiesTable
     */
    private function facilitiesTable(): FacilitiesTable
    {
        /** @var \App\Model\Table\FacilitiesTable */
        return $this->fetchTable('Facilities');
    }

    /**
     * @return \App\Model\Table\CourtsTable
     */
    private function courtsTable(): CourtsTable
    {
        /** @var \App\Model\Table\CourtsTable */
        return $this->fetchTable('Courts');
    }

    /**
     * @return \App\Model\Table\EventCourtsTable
     */
    private function eventCourtsTable(): EventCourtsTable
    {
        /** @var \App\Model\Table\EventCourtsTable */
        return $this->fetchTable('EventCourts');
    }
}
