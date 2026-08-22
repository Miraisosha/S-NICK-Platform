<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Model\Entity\Event;
use App\Model\Table\CourtsTable;
use App\Model\Table\EventCourtsTable;
use App\Model\Table\EventCourtUsageTimesTable;
use App\Model\Table\EventsTable;
use App\Model\Table\EventStaffRolesTable;
use App\Model\Table\EventStaffTable;
use App\Model\Table\RolesTable;
use App\Service\Event\EventService;
use Authentication\IdentityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Exception\PersistenceFailedException;

/**
 * JSON API for SCR-OPR-2401〜2404 イベント管理 (operator-authenticated, see
 * Api\V1\AppController / Application::getAuthenticationService()).
 */
class EventsController extends AppController
{
    /**
     * Events the current user owns or manages (SCR-OPR-2401 イベント一覧).
     *
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        $this->request->allowMethod(['get']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        $events = $this->eventsTable()->find('active')
            ->find('forUser', userId: $identity->getIdentifier())
            ->orderBy(['start_at' => 'DESC'])
            ->all();

        return $this->json(['events' => $events]);
    }

    /**
     * @param string|null $id Event id.
     * @return \Cake\Http\Response
     */
    public function view(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        $event = $this->findManageableEvent($id, (int)$identity->getIdentifier());
        if ($event === null) {
            return $this->jsonError('not_found', __('イベントが見つかりません。'), 404);
        }

        return $this->json(['event' => $event]);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function add(): Response
    {
        $this->request->allowMethod(['post']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        try {
            $event = $this->eventService()->create(
                (array)$this->request->getData(),
                (int)$identity->getIdentifier(),
            );
        } catch (PersistenceFailedException $e) {
            return $this->validationError($e->getEntity()->getErrors());
        }

        return $this->json(['event' => $event], 201);
    }

    /**
     * @param string|null $id Event id.
     * @return \Cake\Http\Response
     */
    public function edit(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'patch', 'put']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        $event = $this->findManageableEvent($id, (int)$identity->getIdentifier());
        if ($event === null) {
            return $this->jsonError('not_found', __('イベントが見つかりません。'), 404);
        }

        $event = $this->eventsTable()->patchEntity($event, (array)$this->request->getData());

        if ($event->hasErrors()) {
            return $this->validationError($event->getErrors());
        }

        try {
            $this->eventsTable()->saveOrFail($event);
        } catch (PersistenceFailedException $e) {
            return $this->validationError($e->getEntity()->getErrors());
        }

        return $this->json(['event' => $event]);
    }

    /**
     * The courts this event has selected (SCR-OPR-2402 "開催場所・使用コート").
     *
     * @param string|null $id Event id.
     * @return \Cake\Http\Response
     */
    public function courts(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        if ($this->findManageableEvent($id, (int)$identity->getIdentifier()) === null) {
            return $this->jsonError('not_found', __('イベントが見つかりません。'), 404);
        }

        $eventCourts = $this->eventCourtsTable()->find()
            ->where(['event_id' => $id])
            ->contain(['Courts'])
            ->all();

        return $this->json(['courts' => $eventCourts]);
    }

    /**
     * Replaces this event's selected courts wholesale (request body:
     * `{"court_ids": [1, 2, 3]}`) - courts are chosen from the admin-managed
     * facility/court master only (SCR-OPR-261), never created here.
     *
     * @param string|null $id Event id.
     * @return \Cake\Http\Response
     */
    public function updateCourts(?string $id = null): Response
    {
        $this->request->allowMethod(['put']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        if ($this->findManageableEvent($id, (int)$identity->getIdentifier()) === null) {
            return $this->jsonError('not_found', __('イベントが見つかりません。'), 404);
        }

        $courtIds = array_map('intval', (array)$this->request->getData('court_ids'));

        if ($courtIds !== []) {
            $activeCount = $this->courtsTable()->find('active')
                ->where(['id IN' => $courtIds])
                ->count();
            if ($activeCount !== count(array_unique($courtIds))) {
                return $this->jsonError(
                    'invalid_courts',
                    __('指定されたコートに、存在しないものが含まれています。'),
                    422,
                );
            }
        }

        $this->eventCourtsTable()->getConnection()->transactional(function () use ($id, $courtIds): void {
            $this->eventCourtsTable()->deleteAll(['event_id' => $id]);
            $now = DateTime::now();
            foreach ($courtIds as $courtId) {
                $eventCourt = $this->eventCourtsTable()->newEntity([
                    'court_id' => $courtId,
                ], ['accessibleFields' => ['event_id' => true]]);
                $eventCourt->patch(['event_id' => (int)$id, 'created' => $now], ['guard' => false]);
                $this->eventCourtsTable()->saveOrFail($eventCourt);
            }
        });

        $eventCourts = $this->eventCourtsTable()->find()
            ->where(['event_id' => $id])
            ->contain(['Courts'])
            ->all();

        return $this->json(['courts' => $eventCourts]);
    }

    /**
     * The per-day, per-court usage times registered for this event
     * (SCR-OPR-2404 "開催日ごと・使用コートごとに利用開始時刻と利用終了時刻を登録する").
     *
     * @param string|null $id Event id.
     * @return \Cake\Http\Response
     */
    public function usageTimes(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        if ($this->findManageableEvent($id, (int)$identity->getIdentifier()) === null) {
            return $this->jsonError('not_found', __('イベントが見つかりません。'), 404);
        }

        $usageTimes = $this->eventCourtUsageTimesTable()->find()
            ->where(['event_id' => $id])
            ->contain(['Courts'])
            ->orderBy(['usage_date' => 'ASC', 'court_id' => 'ASC'])
            ->all();

        return $this->json(['usageTimes' => $usageTimes]);
    }

    /**
     * Replaces this event's usage times wholesale (request body:
     * `{"usage_times": [{"court_id", "usage_date", "start_time", "end_time"}, ...]}`).
     * Each court_id must be one of the event's currently-selected courts.
     *
     * @param string|null $id Event id.
     * @return \Cake\Http\Response
     */
    public function updateUsageTimes(?string $id = null): Response
    {
        $this->request->allowMethod(['put']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        if ($this->findManageableEvent($id, (int)$identity->getIdentifier()) === null) {
            return $this->jsonError('not_found', __('イベントが見つかりません。'), 404);
        }

        $selectedCourtIds = $this->eventCourtsTable()->find()
            ->where(['event_id' => $id])
            ->all()
            ->extract('court_id')
            ->toArray();

        $rows = (array)$this->request->getData('usage_times');
        $entities = [];
        foreach ($rows as $row) {
            $courtId = isset($row['court_id']) ? (int)$row['court_id'] : null;
            if ($courtId === null || !in_array($courtId, $selectedCourtIds, true)) {
                return $this->jsonError(
                    'invalid_court',
                    __('利用時間には、このイベントで選択済みのコートだけを指定できます。'),
                    422,
                );
            }

            $entity = $this->eventCourtUsageTimesTable()->newEntity((array)$row);
            $entity->set('event_id', (int)$id, ['guard' => false]);
            if ($entity->hasErrors()) {
                return $this->validationError($entity->getErrors());
            }
            $entities[] = $entity;
        }

        $this->eventCourtUsageTimesTable()->getConnection()->transactional(function () use ($id, $entities): void {
            $this->eventCourtUsageTimesTable()->deleteAll(['event_id' => $id]);
            foreach ($entities as $entity) {
                $this->eventCourtUsageTimesTable()->saveOrFail($entity);
            }
        });

        $usageTimes = $this->eventCourtUsageTimesTable()->find()
            ->where(['event_id' => $id])
            ->contain(['Courts'])
            ->orderBy(['usage_date' => 'ASC', 'court_id' => 'ASC'])
            ->all();

        return $this->json(['usageTimes' => $usageTimes]);
    }

    /**
     * Loads the event only if it exists, is active, and the current user
     * may manage it (owner or `event_manager` staff - EventsTable::findForUser()).
     *
     * @param string|null $id Event id.
     * @param int $userId Current user id.
     * @return \App\Model\Entity\Event|null
     */
    private function findManageableEvent(?string $id, int $userId): ?Event
    {
        try {
            /** @var \App\Model\Entity\Event */
            return $this->eventsTable()->find('active')
                ->find('forUser', userId: $userId)
                ->where(['Events.id' => $id])
                ->firstOrFail();
        } catch (RecordNotFoundException) {
            return null;
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
    private function requireIdentity(): ?IdentityInterface
    {
        return $this->Authentication->getIdentity();
    }

    /**
     * @return \App\Service\Event\EventService
     */
    private function eventService(): EventService
    {
        return new EventService(
            $this->eventsTable(),
            $this->fetchEventStaffTable(),
            $this->fetchEventStaffRolesTable(),
            $this->fetchRolesTable(),
        );
    }

    /**
     * @return \App\Model\Table\EventsTable
     */
    private function eventsTable(): EventsTable
    {
        /** @var \App\Model\Table\EventsTable */
        return $this->fetchTable('Events');
    }

    /**
     * @return \App\Model\Table\EventStaffTable
     */
    private function fetchEventStaffTable(): EventStaffTable
    {
        /** @var \App\Model\Table\EventStaffTable */
        return $this->fetchTable('EventStaff');
    }

    /**
     * @return \App\Model\Table\EventStaffRolesTable
     */
    private function fetchEventStaffRolesTable(): EventStaffRolesTable
    {
        /** @var \App\Model\Table\EventStaffRolesTable */
        return $this->fetchTable('EventStaffRoles');
    }

    /**
     * @return \App\Model\Table\RolesTable
     */
    private function fetchRolesTable(): RolesTable
    {
        /** @var \App\Model\Table\RolesTable */
        return $this->fetchTable('Roles');
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

    /**
     * @return \App\Model\Table\EventCourtUsageTimesTable
     */
    private function eventCourtUsageTimesTable(): EventCourtUsageTimesTable
    {
        /** @var \App\Model\Table\EventCourtUsageTimesTable */
        return $this->fetchTable('EventCourtUsageTimes');
    }
}
