<?php
declare(strict_types=1);

namespace App\Service\Event;

use App\Model\Entity\Event;
use App\Model\Table\EventsTable;
use App\Model\Table\EventStaffRolesTable;
use App\Model\Table\EventStaffTable;
use App\Model\Table\RolesTable;
use Cake\I18n\DateTime;

/**
 * SCR-OPR-2403 イベント新規登録: creates an event and registers its
 * creator as owner. "メール確認済みユーザーは誰でもイベントを作成でき、
 * 作成した個人をイベント所有者とする" (docs/specifications/020_UserRoles.md) -
 * ownership is expressed both as `events.owner_user_id` and as an
 * `event_staff` row with the `event_owner` role, since
 * EventsTable::findForUser() and future staff-management screens need the
 * latter.
 */
class EventService
{
    /**
     * @param \App\Model\Table\EventsTable $eventsTable Events table.
     * @param \App\Model\Table\EventStaffTable $eventStaffTable EventStaff table.
     * @param \App\Model\Table\EventStaffRolesTable $eventStaffRolesTable EventStaffRoles table.
     * @param \App\Model\Table\RolesTable $rolesTable Roles table.
     */
    public function __construct(
        private readonly EventsTable $eventsTable,
        private readonly EventStaffTable $eventStaffTable,
        private readonly EventStaffRolesTable $eventStaffRolesTable,
        private readonly RolesTable $rolesTable,
    ) {
    }

    /**
     * @param array<string, mixed> $data Submitted event fields.
     * @param int $ownerUserId The creating user, who becomes the owner.
     * @return \App\Model\Entity\Event
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     */
    public function create(array $data, int $ownerUserId): Event
    {
        /** @var \App\Model\Entity\Event $event */
        $event = $this->eventsTable->newEntity($data);
        $event->patch([
            'owner_user_id' => $ownerUserId,
            'publication_status' => Event::PUBLICATION_STATUS_PUBLISHED,
        ], ['guard' => false]);

        return $this->eventsTable->getConnection()->transactional(function () use ($event, $ownerUserId): Event {
            $this->eventsTable->saveOrFail($event);
            $this->assignOwnerRole($event, $ownerUserId);

            return $event;
        });
    }

    /**
     * @param \App\Model\Entity\Event $event The just-created event.
     * @param int $ownerUserId The owner.
     * @return void
     */
    private function assignOwnerRole(Event $event, int $ownerUserId): void
    {
        $role = $this->rolesTable->find()
            ->where(['code' => 'event_owner'])
            ->firstOrFail();

        $staff = $this->eventStaffTable->newEntity([
            'event_id' => $event->id,
            'user_id' => $ownerUserId,
            'membership_status' => 'active',
            'joined_at' => DateTime::now(),
        ], ['accessibleFields' => ['*' => true]]);
        $this->eventStaffTable->saveOrFail($staff);

        $staffRole = $this->eventStaffRolesTable->newEntity([
            'event_staff_id' => $staff->id,
            'role_id' => $role->id,
        ], ['accessibleFields' => ['*' => true]]);
        $this->eventStaffRolesTable->saveOrFail($staffRole);
    }
}
