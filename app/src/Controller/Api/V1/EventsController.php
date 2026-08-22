<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Model\Entity\Event;
use App\Model\Table\EventsTable;
use App\Model\Table\EventStaffRolesTable;
use App\Model\Table\EventStaffTable;
use App\Model\Table\RolesTable;
use App\Service\Event\EventService;
use Authentication\IdentityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
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
}
